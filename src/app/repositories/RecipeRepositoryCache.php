<?php

class RecipeRepositoryCache extends RecipeRepository
{
  const CACHE_KEY = "recipeRepository";

  protected function read()
  {
    if(Cache::has(self::CACHE_KEY))
    {
      return Cache::get(self::CACHE_KEY);
    }
    $database = parent::read();
    Cache::put(self::CACHE_KEY, $database, 60);
    return $database;
  }
}