<?php

class Recipe implements Robbo\Presenter\PresentableInterface
{
  protected $data;
  protected $item;

  public function __construct(Repositories\Item $item)
  {
    $this->item = $item;
  }

  public function load($data)
  {
    $this->data = $data;
  }

  public function __get($name)
  {
    $method = "get".str_replace(" ", "", ucwords(str_replace("_", " ", $name)));
    if (method_exists($this, $method))
      return $this->{$method}();
    if (isset($this->data->$name))
      return $this->data->$name;
    return null;
  }

  public function getSkillsRequired ()
  {
    if (!isset($this->data->skills_required))
      return null;

    $skills = $this->data->skills_required;
    if(!isset($skills[0]))
      return array();
    if(!is_array($skills[0]))
      return array($skills);

    return array_map(function ($i) use ($skills) { 
      return $i; 
    }, $skills);
  }


  public function getResult()
  {
    return $this->item->find($this->data->result);
  }

  public function getHasTools()
  {
    return isset($this->data->tools);
  }

  public function getHasComponents()
  {
    return isset($this->data->components);
  }

  public function getTools()
  {
    return array_map(function($group) {
      return array_map(function($tool) {
        list($id, $amount) = $tool;
        return array($this->item->find($id), $amount);
      }, $group);
    }, $this->data->tools);
  }

  public function getComponents()
  {
    return array_map(function($group) {
      return array_map(function($component) {
        list($id, $amount) = $component;
        return array($this->item->find($id), $amount);
      }, $group);
    }, $this->data->components);
  }

  public function getBookLearn()
  {
    return array_map(function ($book) {
      return array($this->item->find($book[0]), $book[1]);
    }, $this->data->book_learn);
  }


  public function getPresenter()
  {
    return new Presenters\Recipe($this);
  }
}
