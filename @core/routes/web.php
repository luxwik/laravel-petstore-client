<?php

use App\Http\Controllers\PetController;
use Illuminate\Support\Facades\Route;



// List pets
Route::get('/', [PetController::class, 'index'])->name('pets.index');

// Show create form
Route::get('/pets/create', [PetController::class, 'create'])->name('pets.create');

// Store new pet
Route::post('/pets', [PetController::class, 'store'])->name('pets.store');

// Show single pet
Route::get('/pets/{id}', [PetController::class, 'show'])->name('pets.show');

Route::post('/pets/{pet}/photo', [PetController::class, 'uploadPhoto'])->name('pets.photo.store');

// Show edit form
Route::get('/pets/{id}/edit', [PetController::class, 'edit'])->name('pets.edit');

// Update existing pet
Route::put('/pets/{id}', [PetController::class, 'update'])->name('pets.update');

// Delete pet
Route::delete('/pets/{id}', [PetController::class, 'destroy'])->name('pets.destroy');
