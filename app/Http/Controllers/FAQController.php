<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FAQ;

class FAQController extends Controller
{
    protected $faq;

    public function __construct(faq $faq) {
        $this->faq = $faq;
    }

    public function index() {
        $faqs = $this->faq->orderBy('id', 'desc')->get();
        return view('faqs.index', [ 'faqs' => $faqs]);
    }

    public function create() {
        return view('faqs.create');
    }
    public  function store(Request $request) {
        $request->validate([
            'question' => 'required',
            'answers' => 'required'
        ]);

        $storeData = [
            'question' => $request->question,
            'answers' => $request->answers
        ];

        $this->faq->create($storeData);
        return redirect()->route('f_a_ques')->with('success', 'FAQs added succssfully.');
    }

    public function delete($id) {
        $faq = $this->faq->findOrFail($id);

        if(!$faq) {
            return back()->with('error', 'faqs are not found');
        }

        $faq->delete();

        return back()->with('success', 'faqs deleted successfully');
    }

    public function getFaqs() {
        try {
            $faqs = $this->faq->orderBy('id', 'desc')->get();
            return response([
                'faqs' => $faqs
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get faqs ' . $e->getMessage()
            ], 500);
        }
    }
}
