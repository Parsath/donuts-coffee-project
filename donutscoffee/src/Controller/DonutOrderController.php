<?php


namespace App\Controller;


use App\Entity\Article;
use App\Entity\LineItem;
use App\Entity\ToppingLineItem;
use App\Entity\Order;
use App\Entity\Topping;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DonutOrderController extends AbstractController
{

//    Load Donuts on order page
    /**
     * @Route("/order", name="app_order")
     */
    public function orderPage(EntityManagerInterface $em,LoggerInterface $logger)
    {
        $logger->info("Onto Order");
        $repository = $em->getRepository(Article::class);
        $toppingRepository = $em->getRepository(Topping::class);

        /** @var array $articles */
        $articles = $repository->findAllNonDeletedArticles();

        /** @var array $toppings */
        $toppings = $toppingRepository->findAllAvailableNonDeletedArticles();

//        $articles = $repository->findBy(
//            array('isDeleted' => 0),
//            array('availability' => 1)
//        );

//        $articles->getDoctrine()->getRepository(FormInputs::class)->
//        findBy(
//            array('uid'=>$uid),
//            array('id'=>'DESC')
//        );

//        if(!$articles) {
//            throw $this->createNotFoundException(sprintf('Sorry we\'re actually encountering a problem x_x'));
//        }


        return $this->render('home/order.html.twig', [
            'articles' => $articles,
            'toppings' => $toppings
        ]);
    }

//    Returns Donuts characteristics so that it gets displayed after clicking "Add To Cart" on Order page
    /**
     * @Route("/order/donut/{slug}", name="article_display", methods={"POST"})
     */
    public function openDonut($slug, EntityManagerInterface $em)
    {
        $repository = $em->getRepository(Article::class);

        /** var Article $article */
        $article = $repository->findOneBy(
            ['slug' => $slug]
        );

        return new JsonResponse([
            'name' => $article->getName(),
            'link' => $article->getLink(),
            'slug' => $article->getSlug(),
            'price' => $article->getPrice(),
            'description' => $article->getDescription(),
            'quantity' => $article->getQuantity(),
        ]);
    }

//    Creates Order and Line Items if Pickup
    /**
     * @Route("/order/pickup", name="order_pickup", methods={"POST"})
     */
    public function pickUpOrder(LoggerInterface $logger,Request $request, EntityManagerInterface $em)
    {
        $donutsArray = $request->get('donutArray');
        $clientName = $request->get('name');
        $clientPhone = $request->get('phone');



        if(!empty($donutsArray))
        {
            $order = new Order();

            $order->setStatus("ongoing");
            $order->setName($clientName);
            $order->setPickup(1);
            $order->setPrice(0);
            $order->setPhone($clientPhone);
            $em->persist($order);
            $em->flush();
            foreach($donutsArray as $donut)
            {
                $lineItem = new LineItem();

                if(gettype($donut['toppings']) == "array"){

                    foreach($donut['toppings'] as $topping){
                        $toppingLineItem = new toppingLineItem();

                        $toppingRepository = $em->getRepository(Topping::class);

                        /** @var Topping $toppingAdded */
                        $toppingAdded = $toppingRepository->findOneBy(
                            ['name' => $topping['name']]
                        );

                        dump($toppingAdded);

                        $toppingLineItem->setTopping($toppingAdded);
                        $toppingLineItem->setToppingPrice($toppingAdded->getPrice());
                        $lineItem->addToppingLineItem($toppingLineItem);

                        $em->persist($toppingLineItem);
                    }
                }

                $repository = $em->getRepository(Article::class);

                /** @var Article $article */
                $article = $repository->findOneBy(
                    ['name' => $donut['name']]
                );

                $lineItem->setQuantity($donut['quantity']);
                $lineItem->setOrderArticle($order);
                $lineItem->setArticle($article);
                $lineItem->setPrice();
                $lineItem->setInstructions($donut['instructions']);

                $order->addPrice($lineItem->getPrice());
                $article->decrementQuantity($donut['quantity']);
                $article->setAvailability();

                $em->persist($article);
                $em->persist($lineItem);
                $em->persist($order);

            }
            $em->flush();
        }


        Return new JsonResponse([
                'clientName' => $clientName,
                'clientPhone' => $clientPhone,
                dump($donutsArray)
        ]);
    }

//    Creates Order and Line Items if Delivery
    /**
     * @Route("/order/delivery", name="order_delivery", methods={"POST"})
     */
    public function deliveryOrder(Request $request, EntityManagerInterface $em)
    {
        $donutsArray = $request->get('donutArray');
        $clientName = $request->get('name');
        $clientPhone = $request->get('phone');
        $clientAddress = $request->get('address');
        if(!empty($donutsArray))
        {
            
            $order = new Order();

            $order->setStatus("ongoing");
            $order->setName($clientName);
            $order->setPickup(0);
            $order->setPhone($clientPhone);
            $order->setAddress($clientAddress);
            $em->persist($order);
            $em->flush();

            foreach($donutsArray as $donut)
            {
                $lineItem = new LineItem();

                if(gettype($donut['toppings']) == "array"){

                    foreach($donut['toppings'] as $topping){
                        $toppingLineItem = new toppingLineItem();

                        $toppingRepository = $em->getRepository(Topping::class);

                        /** @var Topping $toppingAdded */
                        $toppingAdded = $toppingRepository->findOneBy(
                            ['name' => $topping['name']]
                        );


                        $toppingLineItem->setTopping($toppingAdded);
                        $toppingLineItem->setToppingPrice($toppingAdded->getPrice());
                        $lineItem->addToppingLineItem($toppingLineItem);

                        $em->persist($toppingLineItem);
                    }
                }


                $repository = $em->getRepository(Article::class);
                /** @var Article $article */
                $article = $repository->findOneBy(
                    ['name' => $donut['name']]
                );

                $lineItem->setQuantity($donut['quantity']);
                $lineItem->setOrderArticle($order);
                $lineItem->setArticle($article);
                $lineItem->setPrice();
                $lineItem->setInstructions($donut['instructions']);

                $order->addPrice($lineItem->getPrice());
                $article->decrementQuantity($donut['quantity']);
                $article->setAvailability();

                $em->persist($article);
                $em->persist($lineItem);
                $em->persist($order);

            }
            $em->flush();
        }


        Return new JsonResponse([
            'clientName' => $clientName,
            'clientPhone' => $clientPhone
        ]);
    }
}