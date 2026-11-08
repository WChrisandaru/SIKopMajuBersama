<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveMemberRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    public function save(SaveMemberRequest $request): RedirectResponse
    {
        $values = $request->safe()->except('id');
        $id = DB::transaction(function () use ($request, $values): int {
            if ($request->filled('id')) {
                $id = $request->integer('id');
                $member = DB::table('members')->where('id', $id)->lockForUpdate()->first();
                abort_unless($member, 404);
                $changed = array_keys(array_filter($values, fn ($value, $key) => $value !== $member->{$key}, ARRAY_FILTER_USE_BOTH));
                DB::table('members')->where('id', $id)->update($values);
                $action = 'Ubah anggota '.$member->number.'; kolom: '.implode(', ', $changed);
            } else {
                DB::table('member_number_sequences')->where('id', 1)->increment('last_number');
                $next = DB::table('member_number_sequences')->where('id', 1)->value('last_number');
                $number = 'MB-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
                $id = DB::table('members')->insertGetId([...$values, 'number' => $number]);
                $action = 'Tambah anggota '.$number;
            }
            DB::table('audit_entries')->insert(['user_id' => $request->user()->id, 'action' => $action, 'created_at' => now()]);

            return $id;
        }, 3);

        return redirect('/anggota/'.$id)->with('success', 'Data anggota berhasil disimpan.');
    }
}
