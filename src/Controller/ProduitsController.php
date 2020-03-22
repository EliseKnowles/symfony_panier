<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Entity\Produit;
use App\Form\PanierType;
use App\Form\ProduitType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProduitsController extends AbstractController
{
    /**
     * @Route("/produits", name="produits")
     */

    public function index(Request $request)
    {
        $pdo= $this->getDoctrine()->getManager();

        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);

        $form->handleRequest($request);
        if ( $form->isSubmitted() && $form->isValid() ) {

            $photo = $form->get('photo')->getData();
            if($photo){
                $nomPhoto = uniqid().'.'.$photo->guessExtension();
                try {
                    $photo->move(
                        $this->getParameter('upload_dir'),
                        $nomPhoto
                    );
                }
                catch (FileException $e) {
                    $this->addFlash('danger', "Impossible d'uploader l'image");
                    return $this->redirectToRoute('produits');
                }

                $produit->setPhoto($nomPhoto);
            }

            $pdo->persist($produit);
            $pdo->flush();
            
            $this->addFlash("success", "Produit ajouté");
        }

        $produits = $pdo->getRepository(Produit::class)->findAll();

        return $this->render('produits/index.html.twig', [
            'produits' => $produits,
            'form_produit_ajout' => $form->createView()
        ]);
    }


    /**
     * PAGE PRODUIT
     * @Route("/produits/{id}", name="produit_view")
     */

    public function produit(Produit $produit=null, Request $request){

        if($produit != null){
            $panier = new Panier($produit);
            $form = $this->createForm(PanierType::class, $panier);
            $form->handleRequest($request);
            if ( $form->isSubmitted() && $form->isValid() ) {
                if ($panier->getQte() <= $produit->getQte()) {
                    $pdo = $this->getDoctrine()->getManager();
                    $pdo->persist($panier);
                    $pdo->flush();

                    $this->addFlash("success", "Produit ajouté au panier");
                }
                else {
                    $this->addFlash("danger", "Pas assez de produits en stock");
                }
            }

            return $this->render('produits/produit.html.twig', [
                'produit' => $produit,
                'form_panier_ajout' => $form->createView()
            ]);
        }

        else{
            $this->addFlash("danger", "Produit introuvable");
            return $this->redirectToRoute('produits');
        }
    }


    /**
     * PAGE SUPPRESSION PRODUIT
     * @Route("/produits/delete/{id}", name="produit_delete")
     */

    public function delete(Produit $produit=null){
        if($produit != null){

            if ($produit->getPhoto() !=null) {
                unlink($this->getParameter('upload_dir'). $produit->getPhoto());
            }

            $pdo = $this->getDoctrine()->getManager();
            $pdo->remove($produit);
            $pdo->flush();

            $this->addFlash("success", "Produit supprimé");
        }
        else{
            $this->addFlash("danger", "Produit introuvable");
        }
        return $this->redirectToRoute('produits');
    }


}