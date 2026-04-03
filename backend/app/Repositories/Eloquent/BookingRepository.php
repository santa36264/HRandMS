<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Models\Booking;

class BookingRepository implements BookingRepositoryInterface
{
    public function all() { return Booking::all(); }
    public function find(int $id) { return Booking::findOrFail($id); }
    public function create(array $data) { return Booking::create($data); }
    public function update(int $id, array $data) { return Booking::findOrFail($id)->update($data); }
    public function delete(int $id) { return Booking::findOrFail($id)->delete(); }
}
