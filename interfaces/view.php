<?php
namespace App\Interface;
interface View
{
    public function render($request, $reponse, $args);
}

?>