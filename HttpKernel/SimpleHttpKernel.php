<?php

namespace Egulias\SecurityDebugCommandBundle\HttpKernel;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

class SimpleHttpKernel implements HttpKernelInterface
{
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        return;
    }
}
