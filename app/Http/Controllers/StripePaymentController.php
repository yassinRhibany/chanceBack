<!-- <?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Http\Modle\User;

// class StripePaymentController extends Controller
// {
//     public function create_Payment_Intent(Request $request)
//     {
 
//         Stripe::setApiKey(env('STRIPE_SECRET'));

//         $amount = $request->input('amount');   

//         $intent = PaymentIntent::create([
//             'amount' => intval($amount * 100), // بالدولار سنت
//             'currency' => 'usd',
//             'payment_method_types' => ['card'],
//             'description' => 'شراء حصة في مشروع فرصة',
//         ]);

   
//         return response()->json([
//             'clientSecret' => $intent->client_secret
//         ]);
//     }

//     public function deposit(Request $request)
// {
//     Stripe::setApiKey(env('STRIPE_SECRET'));

//      $user = auth()->user();
//     $amount = $request->amount * 100; // Stripe uses cents

//     $paymentIntent = \Stripe\PaymentIntent::create([
//         'amount' => $amount,
//         'currency' => 'usd',
//         'payment_method' => $request->payment_method_id,
//         'confirmation_method' => 'manual',
//         'confirm' => true,
//     ]);

//     // بعد نجاح الدفع
//     if ($paymentIntent->status === 'succeeded') {
//         $user->increment('wallet', $request->amount);
//         return response()->json(['success' => true]);
//     }

//     return response()->json(['error' => 'Payment failed'], 400);
// }
// public function withdraw(Request $request)
// {
//     Stripe::setApiKey(env('STRIPE_SECRET'));

//     // $user = auth()->user();
//     $amount = $request->amount;

//     if ($user->wallet < $amount) {
//         return response()->json(['error' => 'Insufficient balance'], 400);
//     }

//     // Stripe Connect account مطلوب (افترض وجود $user->stripe_account_id)
//     $transfer = \Stripe\Transfer::create([
//         'amount' => $amount * 100,
//         'currency' => 'usd',
//         'destination' => $user->stripe_account_id,
//     ]);

//     $user->decrement('wallet', $amount);

//     return response()->json(['success' => true, 'transfer' => $transfer]);
// }
// } -->
