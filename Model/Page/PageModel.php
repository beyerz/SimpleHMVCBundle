<?php
/**
 * This file is part of the Beyerz/SimpleHMVCBundle package.
 *
 * Copyright (c) 2017. Lance Bailey <bailz777@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Beyerz\SimpleHMVCBundle\Model\Page;
use Beyerz\SimpleHMVCBundle\Model\Element\ElementModel;

/**
 * Class PageModel
 * @author Lance Bailey <bailz777@gmail.com>
 */
abstract class PageModel extends ElementModel
{

    /**
     * Use this function to include global elements
     * that should appear across all your elements
     *
     * @return $this
     */
    public function registerDefaultElements()
    {
        return $this;
    }
}