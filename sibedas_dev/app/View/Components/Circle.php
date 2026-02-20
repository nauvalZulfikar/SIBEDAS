<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Circle extends Component
{
    public $document_title;
    public $document_color;
    public $document_type;
    public $document_id;
    public $visible_small_circle;
    public $document_url;
    public function __construct($document_id = "",$document_title = "", $document_type = "", $document_color = "#020c5c", $visible_small_circle = true, $document_url = "#")
    {
        $this->document_title = $document_title;
        $this->document_color = $document_color;
        $this->document_type = $document_type;
        $this->document_id = $document_id;
        $this->visible_small_circle = $visible_small_circle;
        $this->document_url = $document_url;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.circle');
    }
}
