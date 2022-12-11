<?php

/**
 * @file
 * Contains LinkService.php.
 */

namespace Drupal\original_language_link;

/**
 * Class LinkService.
 */
class LinkService {

  /**
   * languageCode provides language code if exist and url without language code
   */
  public function languageCode($path) {
    $languages = \Drupal::languageManager()->getLanguages();
    $language_options = [];
    foreach ($languages as $langcode => $language) {
      $language_options[$langcode] = $langcode;
    }
    $url_fragment = explode('/', $path);
    $first_url_fragment = $url_fragment[1];
    $canonical_url = $path;
    $language_code = NULL;
    if(in_array($first_url_fragment, $language_options)) {
      $language_code = $first_url_fragment;
      unset($url_fragment[1]);
      $canonical_url = implode('/', $url_fragment);
    }
    return array($language_code, $canonical_url);
  }

  /**
   * Load the node from $canonical_url
   */
  public function loadEntity($path)  {

    $language_code = $this->languageCode($path)[0];
    $canonical_url = $this->languageCode($path)[1];
     // Load node if node id is present in $canonical_url
    if (preg_match('/node\/(\d+)/', $canonical_url, $matches)) {
      $node_id = $matches[1];
      $entity = \Drupal::entityTypeManager()->getStorage('node')->load($node_id);
    } 
    else {
      // Load node if $canonical_url is an alias
      $path_by_alias = \Drupal::service('path_alias.manager')->getPathByAlias($canonical_url, $language_code);

      if (preg_match('/node\/(\d+)/', $path_by_alias, $matches)) {
        $entity = \Drupal::entityTypeManager()->getStorage('node')->load($matches[1]);
      } 
    }
    return $entity;
  }

  /**
   * Load the node from $canonical_url
   */
  public function processLink($path) {

    $language_code = $this->languageCode($path)[0];
    $canonical_url = $this->languageCode($path)[1];
    $entity = $this->loadEntity($path);

    if (isset($entity)) {
      
      $entity_url = $entity->toUrl()->toString();
      // Return the alias of untranslated node
      if (!$entity->hasTranslation($language_code)) {
        $alias = \Drupal::service('path_alias.manager')->getAliasByPath($entity_url);
        return $alias;
      }
      // Return alias of the translated node if $path is not uri 
      elseif ($entity_url != $path) {
        $alias_translated = \Drupal::service('path_alias.manager')->getAliasByPath($canonical_url, $language_code);
        return $alias_translated;
      }
    }
    else {
      return $path;
    }
    
  }  

}
