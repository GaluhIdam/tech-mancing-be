<?php

namespace App\Http\Controllers;

use App\Models\Acara;
use App\Models\Pemancingan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AcaraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        if ($request->get('page') && $request->get('paginate')) {
            $paginate = $request->get('paginate');
        } else {
            $paginate = 10;
        }

        $data = Acara::with('pemancinganAcara')
            ->where('status', 1)
            ->when($search, function ($query) use ($search) {
                $query->where('nama_acara', 'like', '%' . $search . '%')
                    ->orWhere('deskripsi',  'like', '%' . $search . '%');
            })
            ->orderBy('updated_at', 'desc')->paginate($paginate);

        return response()->json([
            'message' => 'Getting Acara',
            'status' => 200,
            'data' => $data,
        ]);
    }


    public function getByUser(Request $request, $id)
    {
        $search = $request->get('search');
        if ($request->get('page') && $request->get('paginate')) {
            $paginate = $request->get('paginate');
        } else {
            $paginate = 10;
        }
        $data = Acara::where('id_user', $id)->with('pemancinganAcara')
            ->when($search, function ($query) use ($search) {
                $query->where('nama_acara', 'like', '%' . $search . '%')
                    ->orWhere('deskripsi',  'like', '%' . $search . '%');
            })
            ->orderBy('updated_at', 'desc')
            ->paginate($paginate);

        return response()->json([
            'message' => 'Getting Acara',
            'status' => 200,
            'data' => $data,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'id_pemancingan' => 'required',
                'id_user' => 'required',
                'nama_acara' => 'required',
                'deskripsi' => 'required',
                'mulai' => 'required',
                'akhir' => 'required',
                'grand_prize' => 'required',
                'gambar' => 'required|mimes:jpg,jpeg,png|max:1048',
            ]
        );

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $data = Acara::create([
            'id_pemancingan' => $request->get('id_pemancingan'),
            'id_user' => $request->get('id_user'),
            'nama_acara' => $request->get('nama_acara'),
            'deskripsi' => $request->get('deskripsi'),
            'mulai' => $request->get('mulai'),
            'akhir' => $request->get('akhir'),
            'status' => null,
            'grand_prize' => $request->get('grand_prize'),
            'gambar' => $request->file('gambar')->hashName(),
            'path' =>
            $request->file('gambar')->storeAs('public/acara', $request->file('gambar')->hashName()),
        ]);
        $request->file('gambar')->storeAs('public/acara', $request->file('gambar')->hashName());
        return response()->json([
            'message' => 'Acara has Created!',
            'status' => 201,
            'data' => $data,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $data = Acara::where('id', $id)->first();
        $pemancingan = Pemancingan::with('userPemancingan', 'acaraPemancingan', 'komentarPemancingan')->where('id', $data->id_pemancingan)->first();
        if ($data) {
            return response()->json([
                'message' => 'Getting Acara!',
                'status' => 201,
                'data' => $data->setAttribute('pemancingan_acara', $pemancingan),
            ]);
        } else {
            return response()->json([
                'message' => 'Sorry, Data with ' . $id . ' not found',
                'status' => 404,
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $findData = Acara::where('id', $id)->first();
        if ($findData) {
            $validator = Validator::make(
                $request->all(),
                [
                    'id_pemancingan' => 'required',
                    'nama_acara' => 'required',
                    'deskripsi' => 'required',
                    'mulai' => 'required',
                    'akhir' => 'required',
                    'grand_prize' => 'required',
                    'gambar' => 'required|mimes:jpg,jpeg,png|max:1048',
                ]
            );

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }
            Storage::delete($findData->path);
            $findData->update([
                'id_pemancingan' => $request->get('id_pemancingan'),
                'nama_acara' => $request->get('nama_acara'),
                'deskripsi' => $request->get('deskripsi'),
                'mulai' => $request->get('mulai'),
                'akhir' => $request->get('akhir'),
                'status' => null,
                'grand_prize' => $request->get('grand_prize'),
                'gambar' => $request->file('gambar')->hashName(),
                'path' =>
                $request->file('gambar')->storeAs('public/acara', $request->file('gambar')->hashName()),
            ]);
            return response()->json([
                'message' => 'Acara has Updated!',
                'status' => 201,
                'data' => $findData,
            ]);
        } else {
            return response()->json([
                'message' => 'Sorry, Data with ' . $id . ' not found',
                'status' => 404,
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $data = Acara::where('id', $id)->first();
        if ($data) {
            $data->delete();
            return response()->json([
                'message' => 'Acara has Deleted!',
                'status' => 201,
                'data' => $data,
            ]);
        } else {
            return response()->json([
                'message' => 'Sorry, Data with ' . $id . ' not found',
                'status' => 404,
            ]);
        }
    }

    public function showImage($filename)
    {
        $path = 'public/acara/' . $filename;
        if (Storage::exists($path)) {
            $file = Storage::get($path);
            return response($file, 200)->header('Content-Type', 'image/jpeg');
        } else {
            return response('Image not found', 404);
        }
    }

    public function statsAcara()
    {
        $waiting = Acara::where('status', null)->count();
        $reject = Acara::where('status', 0)->count();
        $approve = Acara::where('status', 1)->count();
        return response()->json([
            'message' => 'Acara Stats!',
            'status' => 200,
            'data' => [
                'menunggu' => $waiting,
                'terima' => $approve,
                'tolak' => $reject,
            ],
        ], 200, [], JSON_FORCE_OBJECT);
    }


    public function getAcaraForAdmin(Request $request, $filter)
    {
        $search = $request->get('search');

        if ($request->get('paginate')) {
            $paginate = $request->get('paginate');
        } else {
            $paginate = 10;
        }

        if ($filter == 'null') {
            $data = Acara::with('pemancinganAcara')
                ->where('status', null)
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($sub_query) use ($search) {
                        $sub_query->where('nama_acara', 'LIKE', "%{$search}%")
                            ->orWhere('deskripsi', 'LIKE', "%{$search}%");
                    });
                })
                ->orderBy('updated_at', 'desc')
                ->paginate($paginate);
        } else {
            $data = Acara::with('pemancinganAcara')
                ->where('status', $filter)
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($sub_query) use ($search) {
                        $sub_query->where('nama_acara', 'LIKE', "%{$search}%")
                            ->orWhere('deskripsi', 'LIKE', "%{$search}%");
                    });
                })
                ->orderBy('updated_at', 'desc')
                ->paginate($paginate);
        }

        return response()->json([
            'message' => 'Getting Acara Data is Successfully!',
            'status' => 200,
            'data' => $data,
        ], 200);
    }

    public function aprroveReject(Request $request, $id)
    {
        $findData = Acara::with('pemancinganAcara')->where('id', $id)->first();
        $findData->update([
            'status' => $request->get('status'),
            'pesan' => $request->get('pesan')
        ]);
        return response()->json([
            'message' => 'Acara Updated!',
            'status' => 200,
            'data' => $findData
        ]);
    }
}
