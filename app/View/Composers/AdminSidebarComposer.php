<?php

namespace App\View\Composers;

use App\Models\Book;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\UserModel;
use Illuminate\View\View;

class AdminSidebarComposer
{
    public function compose(View $view)
    {
        $view->with([
            'pendingOrders'  => Order::where('status', 'pending')->count(),
            'pendingReturns' => ReturnRequest::where('status', 'pending')->count(),
            'totalProducts'  => Book::count(),
            'totalClients'   => UserModel::has('orders')->count(),
        ]);
    }
}
