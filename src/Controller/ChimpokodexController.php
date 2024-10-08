<?php

namespace App\Controller;

use App\Entity\Chimpokodex;
use OpenApi\Attributes as OA;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ChimpokodexRepository;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ChimpokodexController extends AbstractController
{
    /**
     * Recupere toutes les entrées Chimpokodex
     *
     * @param ChimpokodexRepository $chimpokodexRepository
     * @param SerializerInterface $serializer
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse
     */
    #[Route('/api/chimpokodex', name: 'app_chimpokodex_getAll', methods: ['GET'])]
    #[IsGranted("ROLE_ADMIN", message: "Hanhanhaaaaaan, vous n'avez pas dis le mot magiqueuuuuh")]
    public function getAllChimpokodexs(
        ChimpokodexRepository $chimpokodexRepository,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $idCache = "getAllChimpokodexs";
        $cachedChimpokos = $cache->get($idCache, function (ItemInterface $item) use ($chimpokodexRepository, $serializer) {
            $item->tag("chimpokodexCache");
            $chimpokolist = $chimpokodexRepository->findAll();
            $jsonChimpokos = $serializer->serialize($chimpokolist, 'json', ['groups' => "chimpokodex"]);
            return $jsonChimpokos;
        });
        return new JsonResponse($cachedChimpokos, Response::HTTP_OK, [], true);
    }

    #[Route('/api/chimpokodex/{chimpokodex}', name: 'app_chimpokodex_get', methods: ['GET'])]
    public function getChimpokodex(
        Chimpokodex $chimpokodex
    ): JsonResponse {
        return $this->json($chimpokodex);
    }


    /**
     * Creer une entrée Chimpokodex
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param TagAwareCacheInterface $cache
     * @param UrlGeneratorInterface $urlGenerator
     * @param ChimpokodexRepository $chimpokodexRepository
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    #[OA\Response(response: 201,
        description: 'Retourne le nouveau chimpokodex',
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(
                ref: new Model(type: Chimpokodex::class, groups: ['chimpokodex'])
            )
        )
    )]
    #[OA\Parameter(
        name: "name",
        in: 'query',
        description: "Le nom du futur chimpokodex",
        schema: new OA\Schema(type: "string")
    )]

    #[Route('/api/chimpokodex', name: 'app_chimpokodex_create', methods: ["POST"])]
    public function createChimpokodex(
        Request $request,
        EntityManagerInterface $entityManager,
        TagAwareCacheInterface $cache,
        UrlGeneratorInterface $urlGenerator,
        ChimpokodexRepository $chimpokodexRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator

    ): JsonResponse {
        dd($request->toArray()['name']);
        $newChimpo = $serializer->deserialize($request->getContent(), Chimpokodex::class, "json");
        $newChimpo->setStatus('on');
        $errors = $validator->validate($newChimpo);
        // dd($errors);
        if ($errors->count() > 0) {
            $messages = [];
            foreach ($errors as $key => $error) {
                $messages[] = $error->getMessage();
            }


            return new JsonResponse($serializer->serialize($messages, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $entityManager->persist($newChimpo);

        $entityManager->flush();

        $cache->invalidateTags(['chimpokodexCache']);

        $jsonChimpokodex = $serializer->serialize($newChimpo, 'json', ['groups' => 'chimpokodex']);

        $location = $urlGenerator->generate('app_chimpokodex_get', ['chimpokodex' => $newChimpo->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonChimpokodex, JsonResponse::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/chimpokodex/{chimpokodex}', name: 'app_chimpokodex_update', methods: ['PUT', 'PATCH'])]
    public function updateChimpokodex(
        Chimpokodex $chimpokodex,
        Request $request,
        EntityManagerInterface $entityManager,
        TagAwareCacheInterface $cache,
        SerializerInterface $serializer,
    ): JsonResponse {

        $newChimpo = $serializer->deserialize($request->getContent(), Chimpokodex::class, "json", [AbstractNormalizer::OBJECT_TO_POPULATE => $chimpokodex]);
        $newChimpo->setStatus('on');
        $entityManager->persist($newChimpo);
        $entityManager->flush();
        $cache->invalidateTags(['chimpokodexCache']);


        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);

    }

    #[Route('/api/chimpokodex/{chimpokodex}', name: 'app_chimpokodex_delete', methods: ['DELETE'])]
    public function deleteChimpokodex(
        Chimpokodex $chimpokodex,
        EntityManagerInterface $entityManager,
        Request $request,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $force = $request->toArray()["force"] ?? false;
        if ($force) {
            $entityManager->remove($chimpokodex);

        } else {
            $chimpokodex->setStatus('off');
            $entityManager->persist($chimpokodex);
        }
        $entityManager->flush();
        $cache->invalidateTags(['chimpokodexCache']);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
