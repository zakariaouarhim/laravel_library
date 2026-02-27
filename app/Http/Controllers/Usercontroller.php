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
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Follow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class Usercontroller extends Controller
{
    function adduser(Request $request){
        try {
            // Log the incoming request
            \Log::info('Form submitted with data:', $request->all());

            $validateData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:user,email',
                'password' => 'required|min:8|confirmed|regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/',
            ], [
                'password.regex' => 'كلمة المرور يجب أن تحتوي على أحرف وأرقام على الأقل',
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
                ->with('error', 'حدث خطأ أثناء التسجيل، يرجى المحاولة لاحقاً.')
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

            if (Auth::attempt($credentials, $requestlogin->has('remember'))) {
                $user = Auth::user();
                
                // Store user info in session for backward compatibility
                session([
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'user_role' => $user->role,
                    'user_avatar' => $user->avatar,
                    'is_logged_in' => true,
                    'user_updated_at' => $user->created_at->locale('ar')->translatedFormat('F Y') // Arabic month/year
                ]);


                // Redirect based on role
                if (in_array($user->role, ['admin', 'super_admin'])) {
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

    // ======================== PASSWORD RESET FUNCTIONS ========================

    /**
     * Show the forgot password form
     */
    public function showForgotPasswordForm()
    {
        return view('forgot-password');
    }

    /**
     * Send password reset link to email
     */
    public function sendResetLink(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email'
            ], [
                'email.required' => 'البريد الإلكتروني مطلوب',
                'email.email' => 'صيغة البريد الإلكتروني غير صحيحة',
            ]);

            // Always return success to prevent user enumeration
            $genericMessage = 'إذا كان هذا البريد مسجلاً، سيتم إرسال رابط إعادة تعيين كلمة المرور';

            $user = UserModel::where('email', $request->email)->first();

            if (!$user) {
                return back()->with('success', $genericMessage);
            }

            // Generate a unique token
            $token = Str::random(60);

            // Store the token in database with expiry (1 hour)
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now()
                ]
            );

            // Send email with reset link
            Mail::send('emails.password-reset', [
                'resetLink' => route('password.reset', ['token' => $token, 'email' => $request->email]),
                'userName' => $user->name
            ], function($message) use ($user) {
                $message->to($user->email)->subject('رابط إعادة تعيين كلمة المرور');
            });

            Log::info('Password reset link sent to: ' . $request->email);

            return back()->with('success', $genericMessage);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error sending reset link:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'حدث خطأ أثناء إرسال الرابط. يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * Show the reset password form
     */
    public function showResetPasswordForm($token, $email)
    {
        // Verify token exists and is not expired (older than 1 hour)
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$resetRecord) {
            return redirect()->route('login2.page')->with('error', 'رابط إعادة التعيين غير صحيح أو انتهت صلاحيته');
        }

        // Check if token is expired (1 hour = 3600 seconds)
        if (now()->diffInSeconds($resetRecord->created_at) > 3600) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return redirect()->route('login2.page')->with('error', 'انتهت صلاحية رابط إعادة التعيين. يرجى طلب رابط جديد');
        }

        return view('reset-password', ['token' => $token, 'email' => $email]);
    }

    /**
     * Reset the password
     */
    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:user,email',
                'token' => 'required|string',
                'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/'
            ], [
                'email.required' => 'البريد الإلكتروني مطلوب',
                'email.email' => 'صيغة البريد الإلكتروني غير صحيحة',
                'email.exists' => 'هذا البريد الإلكتروني غير موجود',
                'password.required' => 'كلمة المرور مطلوبة',
                'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
                'password.confirmed' => 'كلمات المرور غير متطابقة',
                'password.regex' => 'كلمة المرور يجب أن تحتوي على أحرف وأرقام على الأقل',
            ]);

            // Verify token
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$resetRecord || !Hash::check($request->token, $resetRecord->token)) {
                return back()->with('error', 'رابط إعادة التعيين غير صحيح');
            }

            // Check expiry
            if (now()->diffInSeconds($resetRecord->created_at) > 3600) {
                DB::table('password_reset_tokens')->where('email', $request->email)->delete();
                return back()->with('error', 'انتهت صلاحية رابط إعادة التعيين');
            }

            // Update user password
            $user = UserModel::where('email', $request->email)->first();
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            // Delete the reset token
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            Log::info('Password reset successfully for: ' . $request->email);

            return redirect()->route('login2.page')->with('success', 'تم إعادة تعيين كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error resetting password:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'حدث خطأ أثناء إعادة تعيين كلمة المرور');
        }
    }

    // ======================== EXISTING FUNCTIONS ========================
    
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
        $wishlist =auth()->user()->wishlist();
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
        $OrderNumber = Order::where('user_id', $userId)->count();

        // Load who the user follows (authors + publishers)
        $userFollows = Follow::where('user_id', $userId)->get();
        $followedAuthors    = Author::whereIn('id',
            $userFollows->where('followable_type', 'author')->pluck('followable_id')
        )->get();
        $followedPublishers = PublishingHouse::whereIn('id',
            $userFollows->where('followable_type', 'publisher')->pluck('followable_id')
        )->get();

        $reviewCount = $reviews->count();
        $quoteCount  = $quotes->count();

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
        'OrderNumber',
        'followedAuthors',
        'followedPublishers',
        'reviewCount',
        'quoteCount'
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

    public function recommendations(Request $request)
    {
        $userId = auth()->id();

        // Get user's favorite category IDs (from reviews rated 4+)
        $favoriteCategories = Book_Review::where('user_id', $userId)
            ->where('rating', '>=', 4)
            ->with('book.category')
            ->get()
            ->pluck('book.category.id')
            ->filter()
            ->unique()
            ->values();

        // Get reviewed book IDs
        $reviewedBookIds = Book_Review::where('user_id', $userId)
            ->pluck('book_id')
            ->toArray();

        // Build the query
        $query = Book::query()->with(['category', 'primaryAuthor']);

        // Category filter
        if ($request->filled('category')) {
            $query->where('category_id', $request->input('category'));
        } elseif ($favoriteCategories->isNotEmpty()) {
            // Default: show books from favorite categories
            $query->whereIn('category_id', $favoriteCategories);
        }

        // Hide already reviewed toggle (default: hide them)
        $hideReviewed = $request->input('hide_reviewed', '1');
        if ($hideReviewed === '1' && !empty($reviewedBookIds)) {
            $query->whereNotIn('id', $reviewedBookIds);
        }

        // Language filter
        if ($request->filled('language')) {
            $query->where('Langue', $request->input('language'));
        }

        // Price range
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->input('price_min'));
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->input('price_max'));
        }

        // Sorting
        switch ($request->input('sort')) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'title':
                $query->orderBy('title', 'asc');
                break;
            case 'rating':
                $query->withAvg('reviews', 'rating')
                      ->orderByDesc('reviews_avg_rating');
                break;
            case 'newest':
            default:
                $query->latest();
                break;
        }

        $books = $query->paginate(12)->appends($request->query());

        // All categories for sidebar (parent categories with children)
        $categories = Category::whereNull('parent_id')
            ->with('children')
            ->get();

        // Wishlist for heart icons
        $wishlistBookIds = auth()->user()->wishlist()->pluck('books.id')->toArray();

        return view('recommendations', compact(
            'books',
            'categories',
            'favoriteCategories',
            'wishlistBookIds',
            'hideReviewed'
        ));
    }

    private function getRecommendations($userId, $limit = 5)
    {
        // Books to exclude: already reviewed + already in wishlist
        $reviewedBookIds = Book_Review::where('user_id', $userId)
            ->pluck('book_id')->toArray();

        $wishlistBookIds = DB::table('wishlists')
            ->where('user_id', $userId)
            ->pluck('book_id')->toArray();

        $excludeIds = array_unique(array_merge($reviewedBookIds, $wishlistBookIds));

        // Signal 1: Categories from highly rated reviews
        $reviewCategories = Book_Review::where('user_id', $userId)
            ->where('rating', '>=', 4)
            ->with('book.category')
            ->get()
            ->pluck('book.category.id')
            ->filter()->unique()->values();

        // Signal 2: Categories from wishlist books
        $wishlistCategories = DB::table('wishlists')
            ->join('books', 'wishlists.book_id', '=', 'books.id')
            ->where('wishlists.user_id', $userId)
            ->whereNotNull('books.category_id')
            ->pluck('books.category_id')
            ->unique()->values();

        // Signal 3: Followed authors and publishers
        $userFollows = Follow::where('user_id', $userId)->get();
        $followedAuthorIds = $userFollows->where('followable_type', 'author')
            ->pluck('followable_id')->toArray();
        $followedPublisherIds = $userFollows->where('followable_type', 'publisher')
            ->pluck('followable_id')->toArray();

        // Merge all category signals (reviews weighted higher by appearing first)
        $allCategories = $reviewCategories->merge($wishlistCategories)
            ->unique()->take(5);

        $recommendations = collect();

        // Priority 1: Books from followed authors/publishers in preferred categories
        if (!empty($followedAuthorIds) || !empty($followedPublisherIds)) {
            $followBooks = Book::whereNotIn('id', $excludeIds)
                ->where('status', 'active')
                ->where(function ($q) use ($followedAuthorIds, $followedPublisherIds) {
                    $q->whereIn('author_id', $followedAuthorIds)
                      ->orWhereIn('publishing_house_id', $followedPublisherIds);
                })
                ->with(['category', 'primaryAuthor'])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->orderByDesc('created_at')
                ->take($limit)
                ->get();

            $recommendations = $recommendations->merge($followBooks);
        }

        // Priority 2: Books from preferred categories (if we need more)
        if ($recommendations->count() < $limit && $allCategories->isNotEmpty()) {
            $catBooks = Book::whereIn('category_id', $allCategories)
                ->whereNotIn('id', $excludeIds)
                ->whereNotIn('id', $recommendations->pluck('id')->toArray())
                ->where('status', 'active')
                ->with(['category', 'primaryAuthor'])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->orderByDesc('reviews_avg_rating')
                ->orderByDesc('reviews_count')
                ->take($limit - $recommendations->count())
                ->get();

            $recommendations = $recommendations->merge($catBooks);
        }

        // Priority 3: Fallback to popular books
        if ($recommendations->count() < $limit) {
            $popularBooks = Book::whereNotIn('id', $excludeIds)
                ->whereNotIn('id', $recommendations->pluck('id')->toArray())
                ->where('status', 'active')
                ->with(['category', 'primaryAuthor'])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->having('reviews_count', '>', 0)
                ->orderByDesc('reviews_avg_rating')
                ->orderByDesc('reviews_count')
                ->take($limit - $recommendations->count())
                ->get();

            $recommendations = $recommendations->merge($popularBooks);
        }

        // Final fallback: latest books
        if ($recommendations->isEmpty()) {
            $recommendations = Book::whereNotIn('id', $excludeIds)
                ->where('status', 'active')
                ->with(['category', 'primaryAuthor'])
                ->latest()
                ->take($limit)
                ->get();
        }

        // Format data for the view
        return $recommendations->map(function($book) {
            return [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->primaryAuthor->name ?? 'مؤلف غير محدد',
                'image' => $book->image ? asset($book->image) : asset('images/default-book.jpg'),
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
    public function resetPasswordAdmin(Request $request, $id)
    {
        try {
            $user = UserModel::findOrFail($id);
            $request->validate(['method' => 'required|in:auto,manual']);
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
            \Log::error('Reset password error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ، يرجى المحاولة لاحقاً.',
            ], 500);
        }
    }

    // ======================== SUPER ADMIN — USER MANAGEMENT ========================

    public function usersIndex(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:100',
            'role'   => 'nullable|in:user,admin',
        ]);

        $search     = $request->input('search', '');
        $roleFilter = $request->input('role', '');

        $query = UserModel::whereIn('role', ['user', 'admin']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name',  'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        if ($roleFilter) {
            $query->where('role', $roleFilter);
        }

        $users       = $query->latest()->paginate(15);
        $totalUsers  = UserModel::where('role', 'user')->count();
        $totalAdmins = UserModel::where('role', 'admin')->count();
        $newThisMonth = UserModel::whereIn('role', ['user', 'admin'])
                            ->where('created_at', '>=', Carbon::now()->startOfMonth())
                            ->count();

        return view('Dashbord_Admin.users', compact('users', 'totalUsers', 'totalAdmins', 'newThisMonth'));
    }

    public function promoteUser($id)
    {
        $target = UserModel::findOrFail($id);

        if ($target->role !== 'user') {
            return response()->json(['success' => false, 'message' => 'يمكن ترقية الزبائن فقط'], 422);
        }

        $target->role = 'admin';
        $target->save();

        Log::info('Role promotion', ['by' => auth()->id(), 'target' => $id, 'from' => 'user', 'to' => 'admin']);

        return response()->json(['success' => true, 'message' => 'تم ترقية المستخدم إلى مشرف بنجاح']);
    }

    public function demoteUser($id)
    {
        $target = UserModel::findOrFail($id);

        if ($target->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'لا يمكنك تخفيض صلاحياتك الخاصة'], 422);
        }

        if ($target->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'يمكن تخفيض المشرفين فقط'], 422);
        }

        $target->role = 'user';
        $target->save();

        Log::info('Role demotion', ['by' => auth()->id(), 'target' => $id, 'from' => 'admin', 'to' => 'user']);

        return response()->json(['success' => true, 'message' => 'تم تخفيض المشرف إلى زبون بنجاح']);
    }

    public function destroy($id)
    {
        $target      = UserModel::findOrFail($id);
        $currentUser = auth()->user();

        // Cannot delete yourself
        if ($target->id === $currentUser->id) {
            return response()->json(['success' => false, 'message' => 'لا يمكنك حذف حسابك الخاص'], 422);
        }

        // Super admins are never deletable via this UI
        if ($target->role === 'super_admin') {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        // Regular admins cannot delete other admins
        if ($currentUser->role === 'admin' && $target->role === 'admin') {
            return response()->json(['success' => false, 'message' => 'غير مصرح بحذف المشرفين'], 403);
        }

        Log::info('User deleted', ['by' => $currentUser->id, 'target' => $id, 'role' => $target->role]);

        $target->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف المستخدم بنجاح']);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,jpg,png,webp|mimetypes:image/jpeg,image/png,image/webp|max:2048|dimensions:max_width=2000,max_height=2000',
        ], [
            'avatar.required' => 'يرجى اختيار صورة',
            'avatar.image' => 'الملف يجب أن يكون صورة',
            'avatar.mimes' => 'الصورة يجب أن تكون بصيغة jpeg, jpg, png أو webp',
            'avatar.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميغابايت',
            'avatar.dimensions' => 'أبعاد الصورة يجب ألا تتجاوز 2000x2000 بكسل',
        ]);

        $user = Auth::user();

        // Delete old avatar if exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Read, resize and convert to WebP
        $image = Image::read($request->file('avatar'));
        $image->scale(width: 300);
        $encoded = $image->toWebp(80);

        // Generate unique filename and save
        $filename = 'avatars/' . uniqid('avatar_') . '.webp';
        Storage::disk('public')->put($filename, (string) $encoded);

        // Update user record
        $user->avatar = $filename;
        $user->save();

        // Update session
        session(['user_avatar' => $filename]);

        return redirect()->route('account.page')->with('success', 'تم تحديث الصورة الشخصية بنجاح');
    }

    public function showLogin2()
    {
        return view('login2');
    }

    public function logoutRedirect()
    {
        return redirect()->route('index.page');
    }
}