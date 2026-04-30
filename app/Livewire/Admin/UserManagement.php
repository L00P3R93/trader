<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggleAdmin(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            return;
        }

        $user->update(['is_admin' => ! $user->is_admin]);
    }

    public function toggleMaster(int $userId): void
    {
        $connection = User::findOrFail($userId)->derivConnection;

        if (! $connection) {
            return;
        }

        $newType = $connection->isMaster() ? 'follower' : 'master';

        if ($newType === 'follower') {
            // Remove any copy settings where this was the master
            $connection->followers()->delete();
        }

        $connection->update(['type' => $newType]);
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->with('derivConnection')
            ->when($this->search, fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('account_no', 'like', "%{$this->search}%")
            )
            ->latest()
            ->paginate(15);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.user-management');
    }
}
