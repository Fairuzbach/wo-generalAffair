<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\WorkOrderResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeneralAffair\WorkOrderGeneralAffair;
use App\Models\GeneralAffair\WorkOrderGaHistory;
use App\Models\Engineering\Plant;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WorkOrderApiController extends Controller
{
    // --- 1. GET: LIST SEMUA WORK ORDER ---
    public function index(Request $request)
    {
        $query = WorkOrderGeneralAffair::query();

        // Debugging: Cek apakah input masuk
        // return response()->json($request->all()); 

        // Filter Search (Jika ada parameter ?search=...)
        if ($request->has('search') && $request->search != '') {
            $query->where('ticket_num', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        // Logika Admin vs User
        if (Auth::user()->role !== 'ga.admin') {
            $query->where('requester_id', Auth::id());
        }

        $workOrders = $query->latest()->paginate(10);

        // Menambahkan parameter query ke link pagination agar search tidak hilang saat pindah halaman
        return WorkOrderResource::collection($workOrders)->appends($request->query());
    }

    // --- 2. GET: DETAIL WORK ORDER ---
    public function show($id)
    {
        $ticket = WorkOrderGeneralAffair::find($id);

        if ($ticket) {
            return response()->json([
                'success' => true,
                'message' => 'Detail Data Work Order',
                // Gunakan new WorkOrderResource untuk 1 data saja
                'data'    => new WorkOrderResource($ticket)
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Data Tidak Ditemukan!',
        ], 404);
    }

    // --- 3. POST: BUAT TIKET BARU ---
    public function store(Request $request)
    {
        // A. Validasi Input (Sesuai Controller Lama)
        $validator = Validator::make($request->all(), [
            'manual_requester_name' => 'required|string|max:255',
            'plant_id'              => 'required',
            'department'            => 'required',
            'description'           => 'required',
            'category'              => 'required',
            'parameter_permintaan'  => 'required',
            'photo'                 => 'nullable|image|max:5120' // Max 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi Gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        // B. Ambil Nama Plant
        $plantData = Plant::find($request->plant_id);
        $plantName = $plantData ? $plantData->name : 'Unknown Plant';

        // C. Upload Foto (Jika ada)
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('wo_ga', 'public');
        }

        // D. Generate Nomor Tiket (Sesuai Controller Lama)
        $dateCode = date('Ymd');
        $prefix = 'woGA-' . $dateCode . '-';
        $lastTicket = WorkOrderGeneralAffair::where('ticket_num', 'like', $prefix . '%')
            ->orderBy('id', 'desc')->first();
        $newSequence  = $lastTicket ? ((int) substr($lastTicket->ticket_num, -3) + 1) : 1;
        $ticketNum = $prefix . sprintf('%03d', $newSequence);

        // E. Simpan Data ke Database
        try {
            $ticket = WorkOrderGeneralAffair::create([
                'ticket_num'             => $ticketNum,
                'requester_id'           => Auth::id() ?? 1, // Fallback ke ID 1 jika testing tanpa login
                'requester_name'         => $request->manual_requester_name,
                'plant'                  => $plantName,
                'department'             => $request->department,
                'description'            => $request->description,
                'category'               => $request->category,
                'parameter_permintaan'   => $request->parameter_permintaan,
                'status'                 => 'pending',
                'status_permintaan'      => $request->status_permintaan ?? 'Normal',
                'photo_path'             => $photoPath,
            ]);

            // F. Simpan History
            WorkOrderGaHistory::create([
                'work_order_id' => $ticket->id,
                'user_id'       => Auth::id() ?? 1,
                'action'        => 'Created (API)',
                'description'   => 'Tiket dibuat via API atas nama: ' . $request->manual_requester_name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Work Order Berhasil Dibuat!',
                'data'    => $ticket
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi Kesalahan Server',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    // --- 4. PUT/PATCH: UPDATE DATA ---
    public function update(Request $request, $id)
    {
        // Cari data tiket
        $ticket = WorkOrderGeneralAffair::find($id);

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Data Tidak Ditemukan!',
            ], 404);
        }

        // Validasi (Boleh kosong/nullable jika tidak ingin diubah semua)
        $validator = Validator::make($request->all(), [
            'department'   => 'sometimes|required',
            'description'  => 'sometimes|required',
            'category'     => 'sometimes|required',
            'status'       => 'sometimes|required|in:pending,in_progress,completed,cancelled,delayed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi Gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Update data
        $ticket->update($request->all());

        // (Opsional) Catat History jika status berubah
        if ($request->has('status')) {
            WorkOrderGaHistory::create([
                'work_order_id' => $ticket->id,
                'user_id'       => Auth::id() ?? 1,
                'action'        => 'Updated (API)',
                'description'   => 'Data/Status diperbarui via API menjadi: ' . $request->status
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data Berhasil Diupdate!',
            'data'    => $ticket
        ], 200);
    }

    // --- 5. DELETE: HAPUS DATA ---
    public function destroy($id)
    {
        $ticket = WorkOrderGeneralAffair::find($id);

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Data Tidak Ditemukan!',
            ], 404);
        }

        // Hapus data (Soft delete jika di model pakai SoftDeletes, atau Permanent)
        $ticket->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data Berhasil Dihapus!',
        ], 200);
    }
}
