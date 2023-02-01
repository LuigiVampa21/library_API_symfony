<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use App\Service\VersioningService;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class BookController extends AbstractController
{
    #[Route('/api/books', name: 'book', methods: ['GET'])]
    public function getAllBooks(
                                BookRepository $bookRepository, 
                                SerializerInterface $serializer, 
                                Request $request,
                                TagAwareCacheInterface $cache
                                ): JsonResponse
    {

        $page = $request->get('page', 1);
        $limit = $request->get('limit',3);

        $idCache = "getAllBooks-" . $page . "-" . $limit;

        $jsonBookList = $cache->get($idCache, function (ItemInterface $item) use ($bookRepository, $page, $limit, $serializer){
            echo ('No fitting request in cache');
            $item->tag('booksCache');
            $bookList = $bookRepository->findAllWithPagination($page, $limit);
            $context = SerializationContext::create()->setGroups(["getBooks"]);
            $context->setVersion("1.0");
            return $serializer->serialize($bookList, 'json', $context);
        });

        return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/books/{id}', name: 'bookDetail', methods: ['GET'])]
    public function getDetailBook(Book $book, SerializerInterface $serializer, VersioningService $versioningService): JsonResponse 
    {
        $version= $versioningService->getVersion();
        $context = SerializationContext::create()->setGroups(["getBooks"]);
        $context->setVersion($version);
        $jsonBook = $serializer->serialize($book, 'json', $context);
        return new JsonResponse($jsonBook, Response::HTTP_OK, ['accept' => 'json'], true);
    }


    #[Route('/api/books/{id}', name: 'deleteBook', methods: ['DELETE'])]
    public function deleteBook(Book $book, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        $em->remove($book);
        $em->flush();
        $cache->invalidateTags(['booksCache']);
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/books', name: 'createBook', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'You are not authorized to create a book.')]
    public function createBook(
                                Request $request, 
                                SerializerInterface $serializer, 
                                EntityManagerInterface $em, 
                                UrlGeneratorInterface $urlGenerator,
                                AuthorRepository $authorRepository,
                                ValidatorInterface $validator,
                                TagAwareCacheInterface $cache
                                ): JsonResponse 
    {

        $book = $serializer->deserialize($request->getContent(), Book::class, 'json');

        $errors = $validator->validate($book);

        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($book);
        $em->flush();
        $cache->invalidateTags(['booksCache']);

        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? -1;

        $book->setAuthor($authorRepository->find($idAuthor));
        $context = SerializationContext::create()->setGroups(["getBooks"]);
        $jsonBook = $serializer->serialize($book, 'json', $context);
        
        $location = $urlGenerator->generate('bookDetail', ['id' => $book->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonBook, Response::HTTP_CREATED, ["Location" => $location], true);
   }

   #[Route('/api/books/{id}', name: 'updateBook', methods: ['PUT'])]
   #[IsGranted('ROLE_ADMIN', message: 'You are not authorized to create a book.')]
    public function updateBook(
                                Request $request, 
                                SerializerInterface $serializer, 
                                EntityManagerInterface $em, 
                                Book $currentBook,
                                AuthorRepository $authorRepository, 
                                TagAwareCacheInterface $cache,
                                ValidatorInterface $validator
                                ): JsonResponse 
    {

        $newBook = $serializer->deserialize($request->getContent(), Book::class, 'json');
        $currentBook->setTitle($newBook->getTitle());
        $currentBook->setCoverText($newBook->getCoverText());

        $errors = $validator->validate($currentBook);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? -1;
    
        $currentBook->setAuthor($authorRepository->find($idAuthor));

        $em->persist($currentBook);
        $em->flush();

        $cache->invalidateTags(["booksCache"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
   }
}
