<?php
/**
 * This file is part of the Beyerz/SimpleHMVCBundle package.
 *
 * Copyright (c) 2017. Lance Bailey <bailz777@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Beyerz\SimpleHMVCBundle\Controller\Page;
use Beyerz\SimpleHMVCBundle\Exception\RedirectException;
use Beyerz\SimpleHMVCBundle\Model\Page\PageModel;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class PageController
 *
 * Extended Symfony Controller to support HMVC view rendering
 * @author Lance Bailey <bailz777@gmail.com>
 */
class PageController extends Controller
{

    /**
     * @param PageModel $model
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderModel(PageModel $model)
    {
        try {
            $context = $model->registerDefaultElements()->registerElements()->buildContext();
            $compiled = $model->compile($context);
        }catch(RedirectException $e){
            return $e->getResponse();
        }
        return $this->render($compiled->getViewPath(), $compiled->toArray());
    }
}