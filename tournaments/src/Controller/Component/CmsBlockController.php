<?php

namespace App\Controller\Component;

use App\Entity\CmsBlock;
use App\Form\CmsBlockType;
use App\Repository\CmsBlockRepository;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;
use Gedmo\Translatable\Entity\Translation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Contracts\Translation\TranslatorInterface;

class CmsBlockController extends AbstractController
{
    /**
     * @param $slug
     * @param CmsBlockRepository $cmsBlockRepository
     * @param string $default
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function getCmsBlock(Request $request,$slug, CmsBlockRepository $cmsBlockRepository,$default='Text personnalisé')
    {
        $em = $this->getDoctrine()->getManager();
        $cmsBlock = $cmsBlockRepository->findOneBy(["slug" => $slug]);
        if($cmsBlock == null){
            $cmsBlock = new CmsBlock();
            $cmsBlock->setLabel($slug);
            $cmsBlock->setSlug($slug);
            $cmsBlock->setType(CmsBlock::TEXT_WYSIWYG);
            $cmsBlock->setDateCreated(new \DateTime());
            $cmsBlock->setDateUpdated(new \DateTime());

            if(preg_match('#illustration#',$slug)){
                $cmsBlock->setType(CmsBlock::ILLUSTRATION);
            }
            $cmsBlock->setContent($default);
            $this->getDoctrine()->getManager()->persist($cmsBlock);
            $this->getDoctrine()->getManager()->flush();
        }

        $translationRepository = $em->getRepository(Translation::class);
        $translation = $translationRepository->findTranslations($cmsBlock);

        $content = isset($translation[$request->getLocale()])?$translation[$request->getLocale()]['content']: $translation[0]['content'];

        return $this->render('component/cms_block/_block.html.twig', [
            'cmsBlock' => $cmsBlock,
            'content' => $content,
        ]);
    }

    /**
     * @Route("/cms/ajax/block/edit/{id}", name="cms_ajax_block_edit")
     * @param Request $request
     * @param CmsBlock $block
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function ajaxBlockEditAction(Request $request, CmsBlock $block)
    {
        $em = $this->getDoctrine()->getManager();
        $form = $this->createForm(CmsBlockType::class, $block);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($block);
            $em->flush();
            return $this->getBlockAction($block->getSlug());
        }
        return $this->render("component/cms_block/_form.html.twig",
            [
                "form"=>$form->createView()
            ]);
    }

    /**
     * @Route("cms/block/edit/{id}", name="cms_block_edit")
     * @param Request $request
     * @param CmsBlock $block
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function blockEdit(Request $request, CmsBlock $block)
    {
        $em = $this->getDoctrine()->getManager();
        $form = $this->createForm(CmsBlockType::class, $block);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $block->setTranslatableLocale($request->getLocale());
            $em->persist($block);
            $em->flush();
            $this->addFlash('success', "Bloc CMS modifié, vérifiez le résultat, puis fermez cet onglet.");
        }

        return $this->render("component/cms_block/form.html.twig",
            [
                "form"=>$form->createView()
            ]);
    }
}
