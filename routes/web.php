<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/tickets');
Route::livewire('/tickets', 'pages::tickets.index')
    ->name('tickets.index');
Route::livewire('/tickets/create', 'pages::tickets.create')
    ->name('tickets.create');
Route::livewire('/tickets/{ticket}', 'pages::tickets.show')
    ->name('tickets.show'); // detailpagina

