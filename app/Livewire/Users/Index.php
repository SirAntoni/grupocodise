<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'password' => [$this->editingId ? 'nullable' : 'required', Password::defaults()],
            'role' => ['required', Rule::exists('roles', 'name')],
        ];
    }

    protected array $validationAttributes = [
        'name' => 'nombre',
        'email' => 'correo',
        'password' => 'contraseña',
        'role' => 'rol',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorize('users.manage');
        $this->reset(['editingId', 'name', 'email', 'password', 'role']);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(int $userId): void
    {
        $this->authorize('users.manage');
        $user = User::query()->findOrFail($userId);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->getRoleNames()->first() ?? '';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('users.manage');
        $data = $this->validate();

        if ($this->editingId) {
            $user = User::query()->findOrFail($this->editingId);
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                ...($data['password'] ? ['password' => $data['password']] : []),
            ]);
            session()->now('ok', 'Usuario actualizado.');
        } else {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'email_verified_at' => now(),
            ]);
            session()->now('ok', 'Usuario creado.');
        }

        $user->syncRoles([$data['role']]);
        $this->showForm = false;
    }

    public function toggleActive(int $userId): void
    {
        $this->authorize('users.manage');

        if ($userId === auth()->id()) {
            session()->now('error', 'No puedes desactivar tu propia cuenta.');

            return;
        }

        $user = User::query()->findOrFail($userId);
        $user->update(['is_active' => ! $user->is_active]);
        session()->now('ok', $user->is_active ? 'Usuario activado.' : 'Usuario desactivado.');
    }

    public function render(): View
    {
        $users = User::query()
            ->with('roles')
            ->when($this->search !== '', fn ($q) => $q->where(fn ($s) => $s
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.users.index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->pluck('name'),
        ]);
    }
}
