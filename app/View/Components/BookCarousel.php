<?php

namespace App\View\Components;

use Illuminate\View\Component;

class BookCarousel extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public $books;
    public $title;

    // Define what data the component accepts
    public function __construct($books, $title)
    {
        $this->books = $books;
        $this->title = $title;
    }
    

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.book-carousel');
    }
}
