<?php

namespace AppBundle\Controller;

use Buzz\Message\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\EntityManager;
use AppBundle\Controller\BaseController;

class HomeController extends Controller
{
    protected $layout = null;

    public function indexAction(Request $request)
    {
        $data = \AppBundle\Controller\BaseController::setMetaData();
        $sql = "SELECT id,parent_id,name,parent_id,alias,image_url,body,meta_keyword,meta_description,position,image_feature,in_home FROM category WHERE category.enabled = 1 AND category.in_home =1";
        $em = $this->getDoctrine()->getManager();
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $categories = $stmt->fetchAll();
        $cate_array_id = [];
        foreach ($categories as $key => $value) {

            array_push($cate_array_id, $value['id']);
        }
        $qb = $em->getRepository('AppBundle:Category')->createQueryBuilder('c');
        $qb->where('c.parent IN (:ids)')
            ->setParameter('ids', $cate_array_id);
        $all_childs = $qb->getQuery()->getArrayResult();
        $all_childs_id = [];
        foreach ($all_childs as $key => $value) {
            array_push($all_childs_id, $value['id']);
        }
        $repository = $em->getRepository('AppBundle:Product');
        $products = $repository->createQueryBuilder('p')
            ->innerJoin('p.categories', 'g')
            ->where('g.id IN (:category_id)')
            ->setParameter('category_id', $all_childs_id)
            ->select('p', 'g.id AS categoryId', 'g.name AS categoryName')
            ->groupBy('g.id')
            ->getQuery()->getArrayResult();
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($products, $request->query->getInt('page', 1), 15);
        $data['items'] = $pagination;
        $data['category'] = $categories;
        return $this->render('AppBundle:Home:index.html.twig', $data);
    }

    public function buildTree(array $elements, $parentId = null, $parent_id_field = 'parent_id')
    {
        $branch = array();
        foreach ($elements as $element) {
            if ($element[$parent_id_field] == $parentId) {
                $children = $this->buildTree($elements, $element['id']);
                $element['children'] = empty($children) ? [] : $children;

                $branch[] = $element;
            }
        }
        return $branch;
    }
}
