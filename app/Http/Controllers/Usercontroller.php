<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserModel; 
use App\Models\Book;
use App\Models\Author;
use App\Models\BookAuthor;
use App\Models\PublishingHouse;
use App\Models\Book_Review;
use App\Models\Quote;
use App\Models\ReadingGoal;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Usercontroller extends Controller
{
    function adduser(Request $request){
        try {
            // Log the incoming request
            \Log::info('Form submitted with data:', $request->all());

            $validateData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:user,email',
                'password' => 'required|min:8|confirmed',
            ]);
            
            \Log::info('Data passed validation');

            // Create user using the UserModel
            $user = UserModel::create([
                'name' => $validateData['name'],
                'email' => $validateData['email'],
                'password' => Hash::make($validateData['password']),
                'role' => 'user'
            ]);
            
            \Log::info('User created successfully:', ['user_id' => $user->id]);

            if ($user) {
                // Login the user using Laravel's Auth system
                Auth::login($user);
                
                // Also store in session for backward compatibility (if needed)
                session([
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'user_role' => $user->role,
                    
                    'is_logged_in' => true
                ]);
                
                return redirect()->route('index.page')->with('success', 'Account created successfully! Welcome ' . $user->name);
            } else {
                throw new \Exception('Failed to create user');
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed:', ['errors' => $e->errors()]);
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Error in user creation:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'Registration failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    function userlogin(Request $requestlogin){
        try {
            // Validate the request data
            $validatedData = $requestlogin->validate([
                'email' => 'required|email',
                'password' => 'required|min:8',
            ]);

            // Attempt to authenticate using Laravel's Auth system
            $credentials = [
                'email' => $validatedData['email'],
                'password' => $validatedData['password']
            ];

            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                
                // Store user info in session for backward compatibility
                session([
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'user_role' => $user->role,
                    'is_logged_in' => true,
                    'user_updated_at' => $user->created_at->locale('ar')->translatedFormat('F Y') // Arabic month/year
                ]);


                // Redirect based on role
                if ($user->role == "admin") {
                    
                    return redirect()->route('admin.Dashbord_Admin.dashboard')->with('success', 'Login successful');
                } else {
                    return redirect()->route('index.page')->with('success', 'Login successful! Welcome back ' . $user->name);
                }
                
            } else {
                // Invalid credentials
                return back()->with('fail', 'Invalid email or password')->withInput();
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            // Log the error and return a generic error message
            Log::error('Login error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('fail', 'An error occurred during login. Please try again.');
        }
    }

    // Add logout function
    function logout(){
        Auth::logout(); // Logout from Laravel's Auth system
        session()->flush(); // Clear all session data
        return redirect()->route('index.page')->with('success', 'Logged out successfully');
    }

    
    public function account()
    {
        $user = auth()->user(); // returns Usermodel instance
        $userId = auth()->id(); // Get authenticated user ID
        $reviews =  Book_Review::where('user_id', $userId)
                            ->with('book') // Load book relationship
                            ->latest() // Order by newest first
                            ->get();
        $lastReview = Book_Review::where('user_id', $userId)
                         ->with('book')
                         ->latest()
                         ->first(); // 👈 Just get the last one
        $lastWishlistBook = auth()->user()
        ->wishlist()
        ->latest('pivot_created_at') // use pivot table timestamps
        ->first(); // only the last one  

        $booksRead = Book_Review::where('user_id', $userId)
        ->where('is_read', 1)
        ->with('book')
        ->count();

        $readingGoal = ReadingGoal::where('user_id', $userId)
                          ->where('year', now()->year)
                          ->first();

        $target = $readingGoal?->target ?? 12; // fallback to 12 if not set
        $progressPercent = min(100, intval(($booksRead / $target) * 100));
        // Get recommendations
        $recommendations = $this->getRecommendations($userId);
        /* $orders = // fetch user orders
        $addresses = // fetch user addresses
        $wishlist = // fetch user wishlist
        $returns = // fetch user returns*/
        $wishlistBookIds = auth()->check()
        ? auth()->user()->wishlist()->pluck('books.id')->toArray()
        : [];
        $quotes=quote::where('user_id', $userId)
                            ->with('book') // Load book relationship
                            ->latest() // Order by newest first
                            ->get();
        $lastQuote = auth()->user()->latestQuote;

        $WishlistBook = auth()->user()
        ->wishlist()
        ->latest('pivot_created_at') // use pivot table timestamps
        ->get(); // only the last one  
        $OrderNumber=Order::where('user_id', $userId)
        ->count();
        return view('account', 
        compact('reviews',
        'recommendations',
        'wishlistBookIds',
        'lastReview',
        'lastWishlistBook',
        'booksRead',
        'progressPercent',
        'readingGoal',
        'quotes',
        'lastQuote',
        'WishlistBook',
        'target',
        'OrderNumber'
        ));
    } 
    public function updateReadingGoal(Request $request)
    {
        $request->validate([
            'target' => 'required|integer|min:1|max:1000',
        ]);

        $goal = ReadingGoal::updateOrCreate(
            ['user_id' => auth()->id(), 'year' => now()->year],
            ['target' => $request->target]
        );

        return back()->with('success', 'تم تحديث هدف القراءة بنجاح');
    }

   
    private function getRecommendations($userId, $limit = 5)
    {
        
        // Get user's favorite categories based on their highly rated books
        $favoriteCategories = Book_Review::where('user_id', $userId)
                                    ->where('rating', '>=', 4)
                                    ->with('book.category')
                                    ->get()
                                    ->pluck('book.category.id')
                                    ->filter()
                                    ->unique()
                                    ->take(3);
        
        // Get user's already reviewed books to exclude them
        $reviewedBookIds = Book_Review::where('user_id', $userId)
                                    ->pluck('book_id')
                                    ->toArray();
        
        if ($favoriteCategories->isEmpty()) {
            // If no reviews yet, show popular books
            $recommendations = Book::whereNotIn('id', $reviewedBookIds)
                                ->with(['category', 'primaryAuthor'])
                                ->withAvg('reviews', 'rating')
                                ->withCount('reviews')
                                ->having('reviews_count', '>', 0)
                                ->orderBy('reviews_avg_rating', 'desc')
                                ->orderBy('reviews_count', 'desc')
                                ->take($limit)
                                ->get();
        } else {
            // Recommend books from favorite categories
           $recommendations = Book::whereIn('category_id', $favoriteCategories)
                ->whereNotIn('id', $reviewedBookIds)
                ->with(['category', 'primaryAuthor'])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->orderByDesc('reviews_avg_rating')
                ->orderByDesc('reviews_count')
                ->take($limit)
                ->get();

            // If still no books (e.g., because they have no reviews), fallback
            if ($recommendations->isEmpty()) {
                $recommendations = Book::whereIn('category_id', $favoriteCategories)
                    ->whereNotIn('id', $reviewedBookIds)
                    ->with(['category', 'primaryAuthor'])
                    ->take($limit)
                    ->get();
            }

        }
        


        // Format data for the view
        return $recommendations->map(function($book) {
            return [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->primaryAuthor->name ?? 'مؤلف غير محدد',
                'image' => $book->image ? asset( $book->image) : asset('images/default-book.jpg'),
                'rating' => round($book->reviews_avg_rating ?? 0),
                'category' => $book->category->name ?? ''
            ];
        });
        

    }
    ////////////////////////////admin dashboard 
    public function dashboard(){
                    $totalOrders = Order::count();
                    $totalRevenue = Order::sum('total_price');
                    $pendingOrders = Order::where('status', 'pending')->count();
                    $deliveredOrders = Order::where('status', 'delivered')->count();
                    $processingOrders = Order::where('status', 'processing')->count();
                    $cancelledOrders = Order::where('status', 'cancelled')->count();
                    
                    $recentOrders = Order::latest()->limit(5)->get();
                    
                    // Calculate increases (for this month vs last month)
                    $startThisMonth = Carbon::now()->startOfMonth();
                    $startLastMonth = Carbon::now()->subMonth()->startOfMonth();
                    $endLastMonth = Carbon::now()->subMonth()->endOfMonth();

                    $ordersThisMonth = Order::where('created_at', '>=', $startThisMonth)->count();
                    $ordersLastMonth = Order::whereBetween('created_at', [$startLastMonth, $endLastMonth])->count();

                    $ordersIncrease = $ordersLastMonth > 0
                        ? round((($ordersThisMonth - $ordersLastMonth) / $ordersLastMonth) * 100)
                        : 100;

                    $revenueThisMonth = Order::where('created_at', '>=', $startThisMonth)->sum('total_price');
                    $revenueLastMonth = Order::whereBetween('created_at', [$startLastMonth, $endLastMonth])->sum('total_price');

                    $revenueIncrease = $revenueLastMonth > 0
                        ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100)
                        : 100;    
                    
                    $pendingThisMonth = Order::where('status', 'pending')
                        ->where('created_at', '>=', $startThisMonth)
                        ->count();

                    $pendingLastMonth = Order::where('status', 'pending')
                        ->whereBetween('created_at', [$startLastMonth, $endLastMonth])
                        ->count();

                    $pendingDecrease = $pendingLastMonth > 0
                        ? round((($pendingLastMonth - $pendingThisMonth) / $pendingLastMonth) * 100)
                        : 0;
                    $deliveredThisMonth = Order::where('status', 'delivered')
                        ->where('created_at', '>=', $startThisMonth)
                        ->count();

                    $deliveredLastMonth = Order::where('status', 'delivered')
                        ->whereBetween('created_at', [$startLastMonth, $endLastMonth])
                        ->count();

                    $deliveredIncrease = $deliveredLastMonth > 0
                        ? round((($deliveredThisMonth - $deliveredLastMonth) / $deliveredLastMonth) * 100)
                        : 100;

                    //weekly revenu
                    $weeklyRevenue = Order::select(
                            DB::raw('DAYOFWEEK(created_at) as day'),
                            DB::raw('SUM(total_price) as total')
                        )
                        ->where('created_at', '>=', Carbon::now()->startOfWeek())
                        ->groupBy('day')
                        ->orderBy('day')
                        ->get();    
                    // Monthly revenue for current year
                    $monthlyRevenue = Order::select(
                            DB::raw('MONTH(created_at) as month'),
                            DB::raw('SUM(total_price) as total')
                        )
                        ->whereYear('created_at', Carbon::now()->year)
                        ->groupBy('month')
                        ->orderBy('month')
                        ->get();
                    // yearly revenue for current year
                    $yearlyRevenue = Order::select(
                        DB::raw('YEAR(created_at) as year'),
                        DB::raw('SUM(total_price) as total')
                    )
                    ->groupBy('year')
                    ->orderBy('year')
                    ->limit(5)
                    ->get();    

                    return view('Dashbord_Admin.dashboard', compact(
                        'totalOrders', 'totalRevenue', 'pendingOrders', 'deliveredOrders',
                        'processingOrders', 'cancelledOrders', 'recentOrders',
                        'ordersIncrease', 'revenueIncrease', 'pendingDecrease', 'deliveredIncrease',
                        'weeklyRevenue','monthlyRevenue','yearlyRevenue'
                    ));        
    }
    public function index()
    {
        $clients = UserModel::has('orders')->get();
        $totalClients = UserModel::has('orders')->count();
        $newClientsThisMonth = UserModel::whereHas('orders', function ($q) {
                $q->whereBetween('created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ]);
            })
            ->whereDoesntHave('orders', function ($q) {
                $q->where('created_at', '<', Carbon::now()->startOfMonth());
            })
            ->count();
        $activeClients = UserModel::whereHas('orders', function ($q) {
                $q->where('created_at', '>=', now()->subDays(30));
            })
            ->count();    
        return view('Dashbord_Admin.client',compact('clients','totalClients','newClientsThisMonth','activeClients'));
    }
    public function showclient($id)
    {
        $user = UserModel::with([
            'orders.orderDetails.book',
            'orders.checkoutDetail'
        ])
        ->findOrFail($id);
        
        return response()->json($user);
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20'
        ]);

        $user = UserModel::findOrFail($id);
        $user->update($request->only(['name', 'email', 'phone']));

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات الزبون بنجاح',
            'user' => $user
        ]);
    }
    public function resetPassword(Request $request, $id)
    {
        try {
            $user = UserModel::findOrFail($id);
            $method = $request->input('method');

            if ($method === 'auto') {
                // Generate random password
                $newPassword = Str::random(12);
                $user->update(['password' => Hash::make($newPassword)]);

                // Optional: Send email with new password
                // Mail::send(...);

                return response()->json([
                    'success' => true,
                    'message' => 'تم توليد كلمة مرور عشوائية وإرسالها للزبون عبر البريد الإلكتروني'
                ]);
            } else {
                // Manual password validation
                $validated = $request->validate([
                    'password' => 'required|string|min:8|confirmed'
                ], [
                    'password.required' => 'كلمة المرور مطلوبة',
                    'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
                    'password.confirmed' => 'كلمات المرور غير متطابقة'
                ]);

                $user->update(['password' => Hash::make($validated['password'])]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم تعيين كلمة المرور الجديدة بنجاح'
                ]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => implode(' ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}