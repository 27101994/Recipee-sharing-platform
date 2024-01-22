<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\RecipeImage;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Storage;

class RecipeController extends Controller
{
    public function createRecipe(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'ingredients' => 'required|string',
            'steps' => 'required|string',
            'cooking_time' => 'required|integer',
            'difficulty' => 'required|string',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        try {
            $recipe = $user->recipes()->create($request->except('images'));

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('recipe_images');

                    RecipeImage::create([
                        'recipe_id' => $recipe->id,
                        'image_path' => $path,
                    ]);
                }
            }

            $recipe = Recipe::with(['user', 'images'])->find($recipe->id);

            return response()->json(['message' => 'Recipe created successfully', 'data' => $recipe]);
        } catch (\Exception $e) {
            // Log the exception for debugging purposes
            \Log::error('Error creating recipe: ' . $e->getMessage());

            return response()->json(['message' => 'Failed to create recipe. Please try again.'], 500);
        }
    }


    public function getRecipes()
    {
        // Retrieve all recipes with associated user and images
        $recipes = Recipe::with(['user', 'images'])->get();

        return response()->json(['data' => $recipes]);
    }

    public function deleteRecipe($recipeId)
    {
        $recipe = Recipe::findOrFail($recipeId);
        // $this->authorize('delete', $recipe); // Ensure the authenticated user can delete the recipe

        // Delete associated images
        $recipe->images()->delete();

        // Delete the recipe
        $recipe->delete();

        return response()->json(['message' => 'Recipe deleted successfully']);
    }

    public function searchRecipes(Request $request)
    {
        // Implement search recipes logic based on your requirements
    }

    public function rateRecipe(Request $request, $recipeId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            // Add other validation rules as needed
        ]);

        $user = Auth::user();
        $recipe = Recipe::findOrFail($recipeId);

        // Check if the user has already rated the recipe
        if ($recipe->ratings()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You have already rated this recipe'], 422);
        }

        // Create a new rating
        $rating = $recipe->ratings()->create([
            'user_id' => $user->id,
            'rating' => $request->input('rating'),
        ]);

        return response()->json(['message' => 'Recipe rated successfully', 'data' => $rating]);
    }

    public function likeRecipe(Request $request, $recipeId)
    {
        $user = Auth::user();
        $recipe = Recipe::findOrFail($recipeId);

        // Check if the user has already liked the recipe
        if ($recipe->likes()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You have already liked this recipe'], 422);
        }

        // Attach the like to the user
        $recipe->likes()->attach($user->id);

        return response()->json(['message' => 'Recipe liked successfully']);
    }
}