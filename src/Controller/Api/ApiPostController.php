<?php

namespace App\Controller\Api;

use DateTime;
use App\Entity\Post;
use App\Repository\PostRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

class ApiPostController extends AbstractController
{
    /**
     * road to get a post from a given id
     * @Route("/api/post/{id}", name="api_post_get_item", methods={"GET"})
     */
    public function getItem(?Post $post)
    {
        if(!$post) 
        {
            return $this->json(
                ['error' => "écrit non trouvé"],
                response::HTTP_NOT_FOUND
            );
        }


        if ($post->getStatus() !== 2 ) {

            $this->denyAccessUnlessGranted('POST_READ', $post, 'TEST');

            return $this->json(
                $post,
                200,
                [],
                ['groups' => 'get_post']
            );
            // return $this->json(
            //     ['error' => "Cette article n'est pas encore été publié"],
            //     403,
            // );
        }

        else
        {
            return $this->json(
                $post,
                200,
                [],
                ['groups' => 'get_post']
            );
        }
    }

    

    /**
     * road to increment nb views of a given post
     * @Route("/api/post/{id}/add-view", name="api_post_add_view", methods={"PUT"})
     */
    public function addView(?Post $post, ManagerRegistry $doctrine)
    {

        if(!$post) {
            return $this->json(
                ['error' => "écrit non trouvé"],
                response::HTTP_NOT_FOUND
            );
        } 

        if ($post->getStatus() !== 2 ) {
            return $this->json(
                ['error' => "Cette article n'est pas encore publié"],
                Response::HTTP_FORBIDDEN
            );
        }

        else 
        
        {
            // update nbViews
            $nbViews = $post->getNbViews();
            $post->setNbViews($nbViews+1);

            // save the modification of the entity
            $entityManager = $doctrine->getManager();
            $entityManager->persist($post);
            $entityManager->flush();
        }

        return $this->json(
            [],
            Response::HTTP_OK,
            [],
        );    
    }


    /**
     * @Route("/api/post", name="api_post_create_item", methods={"POST"})
     * @isGranted("ROLE_USER", message="Vous devez être connecté")
     */
    public function createItem(Request $request, SerializerInterface $serializer, ManagerRegistry $doctrine, ValidatorInterface $validatorInterface)
    {
        // get the user connected thanks to JWT token 
        $user = $this->getUser();

        // get the json of the request
        $jsonContent = $request->getContent();

        try 
        {
        // deserialize the json into post entity
        $post = $serializer->deserialize($jsonContent, Post::class, 'json');
        } 
        catch (NotEncodableValueException $e) 
        {
            return $this->json(
                ["error" => "JSON INVALIDE"],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // set the user with the connected user 
        $post->setUser($user);

        // check if the post is correctly writen 
        $errors = $validatorInterface->validate($post);

        if(count($errors) > 0)
        {
            return $this->json(
                $errors, 
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }


        // save the modification of the entity
        $entityManager = $doctrine->getManager();
        $entityManager->persist($post);
        $entityManager->flush();

        return $this->json(
            $post,
            Response::HTTP_CREATED,
            [],
            ['groups' => 'get_post']
        );
    }

    /**
     * road to delete a post from a given id
     * @Route("/api/post/{id}", name="api_post_delete_item", methods={"DELETE"})
     * @isGranted("ROLE_USER", message="Vous devez être connecté")
     */
    public function deleteItem(ManagerRegistry $doctrine, ?Post $post)
    {
        // Possible only if the logged-in user is the author of the writing or is an admin        
        $this->denyAccessUnlessGranted('POST_REMOVE', $post);
        
        if(!$post) 
        {
            return $this->json(
                ['error' => "écrit non trouvé"],
                response::HTTP_NOT_FOUND
            );
        }

        else
        {
            // save the modification of the entity
            $entityManager = $doctrine->getManager();
            $entityManager->remove($post);
            $entityManager->flush();


            return $this->json(
                [],
                204,
            );
        }
    }

    /**
     * road to get a post from a given id
     * @Route("/api/post/{id}", name="api_post_update_item", methods={"PUT"})
     * @isGranted("ROLE_USER", message="Vous devez être connecté")
     */
    public function updateItem(ManagerRegistry $doctrine, ?Post $post, Request $request, SerializerInterface $serializer, ValidatorInterface $validatorInterface)
    {

        $this->denyAccessUnlessGranted('POST_EDIT', $post);
        
        if(!$post) 
        {
            return $this->json(
                ['error' => "écrit non trouvé"],
                response::HTTP_NOT_FOUND
            );
        }

        else
        {
            // get the json
            $jsonContent = $request->getContent();

            try 
            {
            // deserialize the json into post entity
            $postModified = $serializer->deserialize($jsonContent, Post::class, 'json', ['object_to_populate' => $post]);

            } 
            catch (NotEncodableValueException $e) 
            {
                return $this->json(
                    ["error" => "JSON INVALIDE"],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            $errors = $validatorInterface->validate($postModified);

            if(count($errors) > 0)
            {
                return $this->json(
                    $errors, 
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            // save the modification of the entity
            $entityManager = $doctrine->getManager();
            $entityManager->persist($postModified);
            $entityManager->flush();

            return $this->json(
                $postModified,
                Response::HTTP_CREATED,
                [],
                ['groups' => 'get_post']
            );
        }
    }

    /**
     * road to get a random post
     * @Route("/api/post-random", name="api_post_get_item_random", methods={"GET"})
     */
    public function getRandomItem(PostRepository $postRepository)
    {
        $randomPost = $postRepository->findOneRandomPost();
        $randomPostId = $randomPost->getId();

        return $this->json(
            $randomPostId,
            200,
            [],
            ['groups' => 'get_post']
        );
    }

    /**
     * road to get the four most liked posts
     * @Route("/api/posts-most-liked", name="api_post_get_most_liked", methods={"GET"})
     */
    public function getMostLiked(PostRepository $postRepository)
    {
        $mostLikedPost = $postRepository->findBy(['status' => 2], ['nbLikes' => 'DESC'], 4);

        return $this->json(
            $mostLikedPost,
            200,
            [],
            ['groups' => 'get_post']
        );
    }

    /**
     * road to get the most recent publicated post (30days)
     * @Route("/api/posts/recent", name="api_post_get_recent", methods={"GET"})
     */
    public function getMostRecent(PostRepository $postRepository)
    {
        $recentPosts = $postRepository->findBy(['status' => 2], ['publishedAt' => 'DESC'] , 4);

        return $this->json(
            $recentPosts,
            200,
            [],
            ['groups' => 'get_post']
        );
    }


    /**
     * road to set a status from a given post to 2 (publicated)
     * @Route("/api/post/{id}/published", name="api_post_update_status_publicated", methods={"PUT"})
     * @isGranted("ROLE_MODERATEUR", message="Vous devez être modérateur")
     */
    public function setStatutToPublicated(ManagerRegistry $doctrine, ?Post $post)
    {

        if(!$post) 
        {
            return $this->json(
                ['error' => "status non trouvé"],
                response::HTTP_NOT_FOUND
            );
        }

        else
        {
            // update status
            $post->setStatus(2);
            $post->setPublishedAt(new DateTime());

            // save the modification of the entity
            $entityManager = $doctrine->getManager();
            $entityManager->persist($post);
            $entityManager->flush();

            return $this->json(
                [],
                Response::HTTP_NO_CONTENT,
            );
        }
    }

    /**
     * road to set a status from a given post to 1 (awaiting for publication)
     * @Route("/api/post/{id}/awaiting", name="api_post_update_status_awaiting", methods={"PUT"})
     * @isGranted("ROLE_USER", message="Vous devez être connecté")
     */
    public function setStatutToAwaiting(ManagerRegistry $doctrine, ?Post $post)
    {
        $this->denyAccessUnlessGranted('POST_SET_STATUS', $post);

        if(!$post) 
        {
            return $this->json(
                ['error' => "écrit non trouvé"],
                response::HTTP_NOT_FOUND
            );
        }

        else
        {
            // update status
            $post->setStatus(1);

            // save the modification of the entity
            $entityManager = $doctrine->getManager();
            $entityManager->persist($post);
            $entityManager->flush();

            return $this->json(
                [],
                Response::HTTP_NO_CONTENT,
            );
        }
    }

    /**
     * road to set a status from a given post to 0 (saved)
     * @Route("/api/post/{id}/saved", name="api_post_update_status_saved", methods={"PUT"})
     * @isGranted("ROLE_USER", message="Vous devez être connecté")
     */
    public function setStatutToSaved(ManagerRegistry $doctrine, ?Post $post)
    {

        if(!$post) 
        {
            return $this->json([
                'error' => "status non trouvé",
                response::HTTP_NOT_FOUND
            ]);
        }

        else
        {
            // update status
            $post->setStatus(0);

            // save the modification of the entity
            $entityManager = $doctrine->getManager();
            $entityManager->persist($post);
            $entityManager->flush();

            return $this->json(
                [],
                Response::HTTP_NO_CONTENT,
            );
        }
    }

    /**
     * road to get the number of like on a given post
     * @Route("/api/post/{id}/like", name="api_post_like", methods={"GET"})
     */
    public function getNbLike(?Post $post): Response
    {
        if(!$post) 
        {
            return $this->json(
                ['error' => "écrit non trouvé"],
                response::HTTP_NOT_FOUND
            );
        }

        $nbLike = $post->getLikedBy()->count();

        return $this->json(
            $nbLike,
            200,
            [],
        );
    }

    /**
     * road to get a post to update
     * @Route("/api/post/awaiting/{id}", name="api_post_get_item_awaiting", methods={"GET"})
     */
    public function getAwaitingItem(?Post $post)
    {
        // authorize access only if the user connected is the author of the post 
        $this->denyAccessUnlessGranted('POST_READ', $post);

        if(!$post) 
        {
            return $this->json(
                ['error' => "écrit non trouvé"],
                response::HTTP_NOT_FOUND
            );
        }


        if ($post->getStatus() == 2 ) {
            return $this->json(
                ['error' => "Cette article est déjà publié"],
                Response::HTTP_FORBIDDEN
            );
        }

        else
        {
            return $this->json(
                $post,
                200,
                [],
                ['groups' => 'get_post']
            );
        }
    }
}
