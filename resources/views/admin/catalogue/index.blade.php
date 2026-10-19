<x-app-layout>
    <x-slot name="header"><x-page-title icon="bx-purchase-tag">Catalogue and price list</x-page-title></x-slot>
    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('admin.services.store') }}" class="grid gap-2 rounded-lg bg-white p-5 shadow-sm">
            @csrf
            <h2 class="font-semibold">Service</h2>
            <input name="code" required placeholder="Code" class="rounded-md border-slate-300 text-sm">
            <input name="name" required placeholder="Name" class="rounded-md border-slate-300 text-sm">
            <input name="category" required placeholder="Category" class="rounded-md border-slate-300 text-sm">
            <input name="cash_price" type="number" min="0" required placeholder="Cash price UGX" class="rounded-md border-slate-300 text-sm">
            <x-primary-button>Add service</x-primary-button>
            <ul class="text-sm">@foreach ($services as $service)<li>{{ $service->code }} {{ $service->name }} · {{ \App\Support\Money::format($service->cash_price) }}</li>@endforeach</ul>
        </form>
        <form method="POST" action="{{ route('admin.items.store') }}" class="grid gap-2 rounded-lg bg-white p-5 shadow-sm">
            @csrf
            <h2 class="font-semibold">Item</h2>
            <input name="sku" required placeholder="SKU" class="rounded-md border-slate-300 text-sm">
            <input name="name" required placeholder="Name" class="rounded-md border-slate-300 text-sm">
            <select name="category" class="rounded-md border-slate-300 text-sm"><option value="medicine">Medicine</option><option value="consumable">Consumable</option><option value="reagent">Reagent</option></select>
            <select name="billing_type" class="rounded-md border-slate-300 text-sm">@foreach ($billingTypes as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach</select>
            <input name="unit" required placeholder="Unit" class="rounded-md border-slate-300 text-sm">
            <input name="cash_price" type="number" min="0" required placeholder="Cash price" class="rounded-md border-slate-300 text-sm">
            <input name="reorder_level" type="number" min="0" value="0" required class="rounded-md border-slate-300 text-sm">
            <x-primary-button>Add item</x-primary-button>
        </form>
        <form method="POST" action="{{ route('admin.lab-tests.store') }}" class="grid gap-2 rounded-lg bg-white p-5 shadow-sm">
            @csrf
            <h2 class="font-semibold">Lab test</h2>
            <input name="code" required placeholder="Code" class="rounded-md border-slate-300 text-sm">
            <input name="name" required placeholder="Name" class="rounded-md border-slate-300 text-sm">
            <select name="service_id" class="rounded-md border-slate-300 text-sm">@foreach ($services as $service)<option value="{{ $service->id }}">{{ $service->name }}</option>@endforeach</select>
            <input name="specimen" placeholder="Specimen" class="rounded-md border-slate-300 text-sm">
            <input name="reference_text" placeholder="Reference range" class="rounded-md border-slate-300 text-sm">
            <select name="reagent_item_id" class="rounded-md border-slate-300 text-sm"><option value="">No reagent</option>@foreach ($items as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select>
            <x-primary-button>Add test</x-primary-button>
        </form>
        <form method="POST" action="{{ route('admin.studies.store') }}" class="grid gap-2 rounded-lg bg-white p-5 shadow-sm">
            @csrf
            <h2 class="font-semibold">Imaging study</h2>
            <input name="code" required placeholder="Code" class="rounded-md border-slate-300 text-sm">
            <input name="name" required placeholder="Name" class="rounded-md border-slate-300 text-sm">
            <select name="service_id" class="rounded-md border-slate-300 text-sm">@foreach ($services as $service)<option value="{{ $service->id }}">{{ $service->name }}</option>@endforeach</select>
            <x-primary-button>Add study</x-primary-button>
        </form>
    </div>
</x-app-layout>
