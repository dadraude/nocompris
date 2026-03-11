<?php

namespace App;

enum ShoppingListItemVisibility: string
{
    case Public = 'public';
    case Private = 'private';
}
