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
    public $link;

    // Define what data the component accepts. $link is where the title points
    // (null = plain text), and must stay optional: moredetail2.blade.php renders
    // four of these without one.
    public function __construct($books, $title, $link = null)
    {
        $this->books = $books;
        $this->title = $title;
        $this->link  = $link;
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
