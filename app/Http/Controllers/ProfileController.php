<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'profile_pic' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        // username and email are not editable - keep existing values
        unset($validated['username'], $validated['email']);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        unset($validated['password_confirmation']);

        if ($request->hasFile('profile_pic')) {
            if (! empty($user->profile_pic) && File::exists(public_path($user->profile_pic))) {
                @File::delete(public_path($user->profile_pic));
            }
            $file = $request->file('profile_pic');
            $imageName = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('uploads/users'), $imageName);
            $validated['profile_pic'] = 'uploads/users/'.$imageName;
        }

        $user->update($validated);

        return response()->json(['success' => true, 'message' => 'Profile updated successfully.']);
    }
}
