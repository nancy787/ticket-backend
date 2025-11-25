<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TermsAndCondition;
use Auth;
use App\Models\Feedback;
use App\Mail\HelpAndSupport;
use Illuminate\Support\Facades\Mail;

class TermsAndServiceController extends Controller
{
    public $termsAndCondition;

    public function __construct(TermsAndCondition $termsAndCondition, Feedback $feedback){
        $this->termsAndCondition = $termsAndCondition;
        $this->feedback          = $feedback;
    }

    public function index() {
        $termAndCondition  =  $this->termsAndCondition->pluck('content')->first();
        return $termAndCondition ;
    }

    public function edit($id) {

        $termAndCondition = $this->termsAndCondition->find($id);

        if(Auth::check() && Auth::user()->hasRole('admin')){
            return view('setting.banner.terms-and-condition', compact('termAndCondition'));
        }

        return redirect('terms-and-condition');
    }

    public function update(Request $request, $id) {
        $termAndCondition = $this->termsAndCondition->find($id);
        $updatedContent = $request->input('content');
        $htmlTemplate = '<!DOCTYPE html>
                            <html lang="en">
                            <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                            <style>
                            body, html {
                                margin: 0;
                                padding: 0;
                                height: 100%;
                                font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
                                color: #fff; /* White text for contrast */
                                background-color: #000; /* Black background */
                                overflow-y: scroll;
                            }
                            body::-webkit-scrollbar {
                                display: none;
                            }
                            body {
                                -ms-overflow-style: none;
                            }
                            body {
                                scrollbar-width: none;
                            }
                            header {
                                display: flex;
                                justify-content: space-between;
                                padding: 20px;
                                background-color: #000; /* Header remains black */
                                align-items: center;
                            }
                            .logo img {
                                height: 40px;
                            }
                            nav {
                                display: flex;
                                gap: 20px;
                            }
                            nav a {
                                color: #FFD700; /* Changed link color to yellow */
                                text-decoration: none;
                                padding: 10px 20px;
                                font-weight: 700;
                            }
                            .nav-highlight {
                                background-color: #FFD700; /* Highlight background color */
                                color: #000; /* Changed highlight text color to black */
                            }
                            .nav-highlight:hover {
                                background-color: #FFA500; /* Hover effect for highlighted nav link */
                                cursor: pointer;
                            }
                            .section {
                                background: #1a1a1a; /* Dark grey background for sections */
                                padding: 30px;
                                border-radius: 8px;
                                box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
                                margin: 20px auto;
                                max-width: 800px; /* Centering the sections */
                                line-height: 1.6;
                            }
                            h2, h3, strong {
                                color: #FFD700;
                                margin-bottom: 15px;/* Changed heading color to yellow */
                                font-weight: bold;
                            }
                            p {
                                font-size: 18px;
                                line-height: 2;
                                margin-bottom: 20px;
                            }
                            ul {
                                margin-top: 15px;
                                margin-bottom: 20px;
                                padding-left: 20px;
                            }
                            li {
                                font-size: 18px;
                                line-height: 2;
                                margin-bottom: 10px;
                            }
                            a {
                                color: #FFD700; /* Changed link color to yellow */
                                text-decoration: none;
                                font-weight: bold;
                            }
                            a:hover {
                                text-decoration: underline;
                            }
                            footer {
                                background-color: #111;
                                padding: 20px;
                                text-align: center;
                                color: #fff;
                                display: flex;
                                flex-direction: column;
                                gap: 10px;
                            }
                            footer a {
                                color: #FFD700; /* Footer link color */
                                text-decoration: none;
                                margin: 0 10px;
                            }
                            footer a:hover {
                                text-decoration: underline;
                            }
                            /* Responsive Styles */
                            @media (max-width: 768px) {
                                .section {
                                    margin: 10px; /* Reduce margin on smaller screens */
                                }
                            }
                        </style>
                            </head>
                            <body>
                            <div class="section">
                                {content}
                            </div>
                            <section>
                            <div>
                            </div>
                            </section>
                            </body>
                            </html>';

        $termAndCondition->content = str_replace('{content}', $updatedContent, $htmlTemplate);
        $termAndCondition->save();

        return redirect()->route('change-terms-and-condition', $id)
                         ->with('success', 'Content updated successfully.');
    }

    public function helpAndSupport() {
        return view('setting.banner.help_and_support');
    }

    public function contactUs(Request $request) {

        $helpAndSupport = $this->feedback->create([
                            'name'     => $request->name,
                            'email'    => $request->email,
                            'subject'  => $request->subject,
                            'message'  => $request->message
                        ]);

        $mailInstanceSupport = new HelpAndSupport($helpAndSupport);
        Mail::to(env('MAIL_USERNAME'))->send($mailInstanceSupport);
        return response()->json(['success' => 'Your query was sent successfully.']);
    }
}
