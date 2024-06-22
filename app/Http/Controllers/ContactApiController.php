<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class ContactApiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(Contact::all()->toArray());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $contact_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($contact_id)
    {
        $contact = Contact::findOrFail($contact_id);

        $contact->deleteOrFail();

        return response(null, 204);
    }

    // /**
    //  * Show the form for creating a new resource.
    //  *
    //  * @return \Illuminate\Http\Response
    //  */
    // public function create()
    // {
    //     //
    // }

    // /**
    //  * Store a newly created resource in storage.
    //  *
    //  * @param  \Illuminate\Http\Request  $request
    //  * @return \Illuminate\Http\Response
    //  */
    // public function store(Request $request)
    // {
    //     //
    // }

    // /**
    //  * Display the specified resource.
    //  *
    //  * @param  int $contact_id
    //  * @return \Illuminate\Http\Response
    //  */
    // public function show($contact_id)
    // {
    //     //
    // }

    // /**
    //  * Show the form for editing the specified resource.
    //  *
    //  * @param  int $contact_id
    //  * @return \Illuminate\Http\Response
    //  */
    // public function edit(Contact $contact_id)
    // {
    //     //
    // }

    // /**
    //  * Update the specified resource in storage.
    //  *
    //  * @param  \Illuminate\Http\Request  $request
    //  * @param  \App\Models\Contact  $contact
    //  * @return \Illuminate\Http\Response
    //  */
    // public function update(Request $request, Contact $contact)
    // {
    //     //
    // }

}
