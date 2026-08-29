<?php

use App\Http\Controllers\Logistics\AirRateCardController;
use App\Http\Controllers\Logistics\BankChargesController;
use App\Http\Controllers\Logistics\BankContractController;
use App\Http\Controllers\Logistics\CarrierController;
use App\Http\Controllers\Logistics\CarrierV2Controller;
use App\Http\Controllers\Logistics\DashboardController;
use App\Http\Controllers\Logistics\DocumentController;
use App\Http\Controllers\Logistics\GDController;
use App\Http\Controllers\Logistics\InlandRateCardController;
use App\Http\Controllers\Logistics\LCController;
use App\Http\Controllers\Logistics\LcPaymentController;
use App\Http\Controllers\Logistics\ManageBookingController;
use App\Http\Controllers\Logistics\ManageBookingV2Controller;
use App\Http\Controllers\Logistics\OceanRateCardController;
use App\Http\Controllers\Logistics\RateCalculatorController;
use App\Http\Controllers\Logistics\ShipmentController;
use App\Http\Controllers\Logistics\SlaContractController;
use App\Http\Controllers\Logistics\SupplierController;
use App\Http\Controllers\Logistics\SupplierV2Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::prefix('logistics')->name('logistics.')->group(function () {

    Route::get('/fx-rates', function () {
        try {
            $rates = Cache::remember('logistics_fx_rates', 30, function () {
                $client = new \GuzzleHttp\Client(['timeout' => 8]);
                // fawazahmed0 currency API — free, no key, CDN-hosted
                $res = $client->get('https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/pkr.json');
                $body = json_decode((string) $res->getBody(), true);

                $pkr = $body['pkr'] ?? [];
                $pairs = ['usd', 'eur', 'gbp', 'aed', 'cny'];
                $out = [];

                foreach ($pairs as $code) {
                    if (!isset($pkr[$code]) || $pkr[$code] == 0) {
                        continue;
                    }
                    // pkr[code] = how many $code per 1 PKR → invert for PKR per 1 $code
                    $out[strtoupper($code)] = round(1 / $pkr[$code], 2);
                }

                return $out;
            });

            if (empty($rates)) {
                return response()->json(['error' => 'Unable to fetch rates'], 503);
            }

            return response()->json(['rates' => $rates]);
        } catch (\Exception) {
            return response()->json(['error' => 'FX service unavailable'], 503);
        }
    })->name('fx-rates');

    Route::controller(DashboardController::class)->group(function () {
        Route::get('/dashboard', 'index')->name('dashboard');
        Route::get('/analytics', 'analytics')->name('analytics.index');
    });

    // Manage Booking v2
    Route::controller(ManageBookingV2Controller::class)->prefix('manage-booking/v2')->name('manage-booking.v2.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/hs-codes/paginate', 'paginateHsCodes')->name('hs-codes.paginate');
        Route::get('/freight-agents', 'searchFreightAgents')->name('freight-agents.search');
        Route::post('/freight-agents', 'storeFreightAgent')->name('freight-agents.store');
        Route::get('/transit-stops', 'searchTransitStops')->name('transit-stops.search');
        Route::post('/transit-stops', 'storeTransitStop')->name('transit-stops.store');
        Route::get('/create', 'create')->name('create');
        Route::post('/sync-apis', 'syncApis')->name('sync-apis');
        Route::post('/process-sync-task', 'processSyncTask')->name('process-sync-task');
        Route::get('/lookup-vehicle', 'lookupVehicle')->name('lookup-vehicle');
        Route::get('/lookup-driver', 'lookupDriver')->name('lookup-driver');
        Route::get('/{id}/details', 'getBookingDetails')->name('details');
        Route::get('/documents', 'documents')->name('documents');
        Route::post('/', 'store')->name('store');
        Route::post('/import', 'importBookings')->name('import');
        Route::get('/download-template', 'downloadTemplate')->name('download-template');
        Route::get('pending-actions', 'pendingActions')->name('pending-actions');
        Route::post('bulk-action', 'bulkAction')->name('bulk-action');
        Route::get('/{id}/child-bookings', 'childBookings')->name('child-booking');
        Route::get('/{id}/child-bookings/{childBookingNumber}/edit', 'editChildBooking')->name('child-booking.edit');
        Route::post('/{id}/child-bookings/{childBookingNumber}', 'updateChildBooking')->name('child-booking.update');
        Route::post('/{id}/child-actions/approve-arrival', 'approveChildArrival')->name('child.approve-arrival');
        Route::post('/{id}/child-actions/reject-arrival', 'rejectChildArrival')->name('child.reject-arrival');
        Route::post('/{id}/child-actions/approve-delivery', 'approveChildDelivery')->name('child.approve-delivery');
        Route::post('/{id}/child-actions/reject-delivery', 'rejectChildDelivery')->name('child.reject-delivery');

        Route::get('/{id}', 'show')->name('show');
        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::get('/{id}/delete-summary', 'deleteSummary')->name('delete-summary');
        Route::post('/{id}', 'update')->name('update');
        Route::post('/{id}/documents', 'storeDocument')->name('documents.store');
        Route::post('/documents/{docId}/update', 'updateDocument')->name('documents.update');
        Route::delete('/documents/{docId}', 'destroyDocument')->name('documents.destroy');
        Route::post('/stages/{stageId}/upload', 'uploadStageFile')->name('stages.upload');
        Route::delete('/{id}', 'destroy')->name('destroy');
        Route::post('{id}/approve-pickup', 'approvePickup')->name('approve-pickup');
        Route::post('{id}/reject-pickup', 'rejectPickup')->name('reject-pickup');
        // Delivery actions
        Route::post('{id}/approve-delivery', 'approveDelivery')->name('approve-delivery');
        Route::post('{id}/reject-delivery', 'rejectDelivery')->name('reject-delivery');
        // Cancel action
        Route::post('{id}/cancel', 'cancel')->name('cancel');

        Route::get('/{id}/tracking', 'tracking')->name('tracking');
        Route::post('/legs/{legId}/status', 'updateLegStatus')->name('legs.update-status');
        Route::post('/legs/{legId}/approve-timing', 'approveLegTiming')->name('legs.approve-timing');
        Route::post('/legs/{legId}/reject-timing', 'rejectLegTiming')->name('legs.reject-timing');
    });

    Route::controller(ManageBookingController::class)->group(function () {
        Route::get('/manage-booking/inbound', 'inbound')->name('manage-booking.index.inbound');
        Route::get('/manage-booking/outbound', 'outbound')->name('manage-booking.index.outbound');
        Route::get('/manage-booking/return', 'return')->name('manage-booking.index.return');
        Route::get('/manage-booking/create', 'create')->name('manage-booking.create');
        Route::get('/manage-booking/data', 'getData')->name('manage-booking.data');
        Route::get('/manage-booking/pending-actions', 'pendingActions')->name('manage-booking.pending-actions');
        Route::get('/manage-booking/zip-lookup', 'zipLookup')->name('manage-booking.zip-lookup');
        Route::post('/manage-booking/{id}/approve-pickup', 'approvePickup')->name('manage-booking.approve-pickup');
        Route::post('/manage-booking/{id}/reject-pickup', 'rejectPickup')->name('manage-booking.reject-pickup');
        Route::post('/manage-booking/{id}/approve-delivery', 'approveDelivery')->name('manage-booking.approve-delivery');
        Route::post('/manage-booking/{id}/reject-delivery', 'rejectDelivery')->name('manage-booking.reject-delivery');
        Route::get('/manage-booking/{id}', 'show')->name('manage-booking.show');
        Route::get('/manage-booking/{id}/edit', 'edit')->name('manage-booking.edit');
        Route::post('/manage-booking/store', 'store')->name('manage-booking.store');
        Route::delete('/manage-booking/destroy/{id}', 'destroy')->name('manage-booking.destroy');
    });

    Route::controller(SupplierController::class)->group(function () {
        Route::get('/suppliers', 'index')->name('suppliers.index');
        Route::get('/suppliers/create', 'create')->name('suppliers.create');
        Route::post('/suppliers', 'store')->name('suppliers.store');
        Route::get('/suppliers/{id}', 'show')->name('suppliers.show');
        Route::get('/suppliers/{id}/edit', 'edit')->name('suppliers.edit');
        Route::put('/suppliers/{id}', 'update')->name('suppliers.update');
        Route::delete('/suppliers/{id}', 'destroy')->name('suppliers.destroy');
    });

    // Supplier Scorecards
    Route::controller(App\Http\Controllers\Logistics\SupplierScorecardController::class)->prefix('v2/suppliers/scorecards')->name('suppliers.scorecard.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::post('/{id}', 'update')->name('update');
    });

    // Suppliers v2
    Route::controller(SupplierV2Controller::class)->prefix('v2/suppliers')->name('suppliers.v2.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::get('/download-template', 'downloadTemplate')->name('download-template');
        Route::post('/import', 'importSuppliers')->name('import');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->name('show');
        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::post('/{id}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');
        Route::patch('/{id}/approve', 'approve')->name('approve');
        Route::patch('/{id}/reject', 'reject')->name('reject');
    });

    Route::controller(CarrierController::class)->group(function () {
        Route::get('/carriers', 'index')->name('carriers.index');
        Route::get('/carriers/create', 'create')->name('carriers.create');
        Route::post('/carriers', 'store')->name('carriers.store');
        Route::get('/carriers/{id}', 'show')->name('carriers.show');
        Route::get('/carriers/{id}/edit', 'edit')->name('carriers.edit');
        Route::put('/carriers/{id}', 'update')->name('carriers.update');
        Route::delete('/carriers/{id}', 'destroy')->name('carriers.destroy');
    });

    Route::controller(App\Http\Controllers\Logistics\EntityController::class)->group(function () {
        Route::get('/entities', 'index')->name('entities.index');
        Route::get('/entities/create', 'create')->name('entities.create');
        Route::post('/entities', 'store')->name('entities.store');
        Route::get('/entities/{id}', 'show')->name('entities.show');
        Route::get('/entities/{id}/edit', 'edit')->name('entities.edit');
        Route::put('/entities/{id}', 'update')->name('entities.update');
        Route::delete('/entities/{id}', 'destroy')->name('entities.destroy');
    });

    Route::controller(App\Http\Controllers\Logistics\PurchaseOrderController::class)->group(function () {
        Route::get('/purchase-orders', 'index')->name('purchase-orders.index');
        Route::get('/purchase-orders/create', 'create')->name('purchase-orders.create');
        Route::get('/purchase-orders/download-template', 'downloadTemplate')->name('purchase-orders.download-template');
        Route::post('/purchase-orders/import', 'importPurchaseOrders')->name('purchase-orders.import');
        Route::post('/purchase-orders', 'store')->name('purchase-orders.store');
        Route::get('/purchase-orders/{purchaseOrder}', 'show')->name('purchase-orders.show');
        Route::get('/purchase-orders/{purchaseOrder}/edit', 'edit')->name('purchase-orders.edit');
        Route::put('/purchase-orders/{purchaseOrder}', 'update')->name('purchase-orders.update');
        Route::delete('/purchase-orders/{purchaseOrder}', 'destroy')->name('purchase-orders.destroy');
        Route::delete('/purchase-orders/attachments/{attachment}', 'deleteAttachment')->name('purchase-orders.attachments.destroy');
    });

    // Bank Contracts (must be before LC /{id} wildcard)
    Route::controller(BankContractController::class)->prefix('lc/bank-contracts')->name('lc.bank-contracts.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::get('/download-template', 'downloadTemplate')->name('download-template');
        Route::post('/import', 'importContracts')->name('import');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->name('show');
        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::put('/{id}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');
    });

    // Bank Charges (must be before LC /{id} wildcard)
    Route::controller(BankChargesController::class)->prefix('lc/bank-charges')->name('lc.bank-charges.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->name('show');
        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::put('/{id}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');
    });

    // LC Payments (must be before LC /{id} wildcard)
    Route::controller(LcPaymentController::class)->prefix('lc/payments')->name('lc.payments.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::get('/download-template', 'downloadTemplate')->name('download-template');
        Route::post('/import', 'import')->name('import');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::put('/{id}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');
    });

    // Letter of Credit
    Route::controller(LCController::class)->prefix('lc')->name('lc.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::get('/landed-cost', 'landedCost')->name('landed-cost');
        Route::get('/payment-tracker', 'paymentTracker')->name('payment-tracker');
        Route::get('/download-template', 'downloadTemplate')->name('download-template');
        Route::post('/import', 'importLC')->name('import');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->name('show');
        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::put('/{id}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');
    });

    // Goods Declaration
    Route::controller(GDController::class)->prefix('gd')->name('gd.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::get('/download-template', 'downloadTemplate')->name('download-template');
        Route::post('/import', 'importGds')->name('import');
        Route::get('/calculator', 'calculator')->name('calculator');
        Route::get('/exam', 'exam')->name('exam');
        Route::get('/landed-cost', 'landedCost')->name('landed-cost');
        Route::get('/payment-tracker', 'paymentTracker')->name('payment-tracker');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::get('/{id}/edit', 'edit')->whereNumber('id')->name('edit');
        Route::put('/{id}', 'update')->whereNumber('id')->name('update');
        Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');
    });

    // Shipments
    Route::controller(ShipmentController::class)->prefix('shipments')->name('shipments.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/imports', 'imports')->name('imports');
        Route::get('/exports', 'exports')->name('exports');
        Route::get('/delayed', 'delayed')->name('delayed');
        Route::get('/pickup', 'pickup')->name('pickup');
    });

    Route::controller(DocumentController::class)->prefix('documents')->name('documents.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/folder', 'getFolder')->name('getFolder');
        Route::post('/folder', 'createFolder')->name('createFolder');
        Route::post('/upload', 'upload')->name('upload');
        Route::get('/download/{id}', 'download')->name('download');
        Route::delete('/delete/{id}', 'delete')->name('delete');
        Route::delete('/folder/{id}', 'deleteFolder')->name('deleteFolder');
    });

    // Route::name('carriers.v2')->group(function () {
    // Route::view('/v2/carriers', 'pages.products.logistics.carriers.v2.index')->name('carriers.v2.index');
    Route::get('/v2/carriers', [CarrierV2Controller::class, 'index'])->name('carriers.v2.index');
    Route::get('/v2/carriers/create', [CarrierV2Controller::class, 'create'])->name('carriers.v2.create');
    Route::get('/v2/carriers/download-template', [CarrierV2Controller::class, 'downloadTemplate'])->name('carriers.v2.download-template');
    Route::post('/v2/carriers/import', [CarrierV2Controller::class, 'importCarriers'])->name('carriers.v2.import');
    Route::post('/v2/carriers', [CarrierV2Controller::class, 'store'])->name('carriers.v2.store');
    Route::post('/v2/carriers/quick-store', [CarrierV2Controller::class, 'quickStore'])->name('carriers.v2.quick-store');
    Route::get('/v2/carriers/{carrier}', [CarrierV2Controller::class, 'show'])->name('carriers.v2.show');
    Route::get('/v2/carriers/{carrier}/edit', [CarrierV2Controller::class, 'edit'])->name('carriers.v2.edit');
    Route::put('/v2/carriers/{carrier}', [CarrierV2Controller::class, 'update'])->name('carriers.v2.update');
    Route::delete('/v2/carriers/{carrier}', [CarrierV2Controller::class, 'destroy'])->name('carriers.v2.destroy');
    Route::patch('/v2/carriers/{carrier}/approve', [CarrierV2Controller::class, 'approve'])->name('carriers.v2.approve');
    Route::patch('/v2/carriers/{carrier}/reject', [CarrierV2Controller::class, 'reject'])->name('carriers.v2.reject');

    Route::get('/carriers/rate-card/ocean', [OceanRateCardController::class, 'index'])->name('carriers.rate-card.ocean.index');
    Route::get('/carriers/rate-card/ocean/create', [OceanRateCardController::class, 'create'])->name('carriers.rate-card.ocean.create');
    Route::post('/carriers/rate-card/ocean', [OceanRateCardController::class, 'store'])->name('carriers.rate-card.ocean.store');
    Route::get('/carriers/rate-card/ocean/{ocean}', [OceanRateCardController::class, 'show'])->name('carriers.rate-card.ocean.show');
    Route::get('/carriers/rate-card/ocean/{ocean}/edit', [OceanRateCardController::class, 'edit'])->name('carriers.rate-card.ocean.edit');
    Route::put('/carriers/rate-card/ocean/{ocean}', [OceanRateCardController::class, 'update'])->name('carriers.rate-card.ocean.update');
    Route::delete('/carriers/rate-card/ocean/{ocean}', [OceanRateCardController::class, 'destroy'])->name('carriers.rate-card.ocean.destroy');

    Route::get('/carriers/rate-card/air', [AirRateCardController::class, 'index'])->name('carriers.rate-card.air.index');
    Route::get('/carriers/rate-card/air/create', [AirRateCardController::class, 'create'])->name('carriers.rate-card.air.create');
    Route::post('/carriers/rate-card/air', [AirRateCardController::class, 'store'])->name('carriers.rate-card.air.store');
    Route::get('/carriers/rate-card/air/{air}', [AirRateCardController::class, 'show'])->name('carriers.rate-card.air.show');
    Route::get('/carriers/rate-card/air/{air}/edit', [AirRateCardController::class, 'edit'])->name('carriers.rate-card.air.edit');
    Route::put('/carriers/rate-card/air/{air}', [AirRateCardController::class, 'update'])->name('carriers.rate-card.air.update');
    Route::delete('/carriers/rate-card/air/{air}', [AirRateCardController::class, 'destroy'])->name('carriers.rate-card.air.destroy');

    Route::get('/carriers/rate-card/inland', [InlandRateCardController::class, 'index'])->name('carriers.rate-card.inland.index');
    Route::get('/carriers/rate-card/inland/create', [InlandRateCardController::class, 'create'])->name('carriers.rate-card.inland.create');
    Route::post('/carriers/rate-card/inland', [InlandRateCardController::class, 'store'])->name('carriers.rate-card.inland.store');
    Route::get('/carriers/rate-card/inland/{inland}', [InlandRateCardController::class, 'show'])->name('carriers.rate-card.inland.show');
    Route::get('/carriers/rate-card/inland/{inland}/edit', [InlandRateCardController::class, 'edit'])->name('carriers.rate-card.inland.edit');
    Route::put('/carriers/rate-card/inland/{inland}', [InlandRateCardController::class, 'update'])->name('carriers.rate-card.inland.update');
    Route::delete('/carriers/rate-card/inland/{inland}', [InlandRateCardController::class, 'destroy'])->name('carriers.rate-card.inland.destroy');

    Route::get('/carriers/rate-card/rate-calculator', [RateCalculatorController::class, 'index'])->name('carriers.rate-card.rate-calculator.index');
    Route::get('/carriers/rate-card/rate-calculator/calculate', [RateCalculatorController::class, 'calculate'])->name('carriers.rate-card.rate-calculator.calculate');
    Route::get('/carriers/rate-card/rate-calculator/zones', [RateCalculatorController::class, 'zones'])->name('carriers.rate-card.rate-calculator.zones');

    Route::prefix('carrier/rate-card/sla-contract')
        ->name('carriers.sla-contract.')
        ->group(function () {

            // Index  – GET /carrier/rate-card/sla-contract
            Route::get('/', [SlaContractController::class, 'index'])
                ->name('index');

            // Create form  – GET /carrier/rate-card/sla-contract/create
            Route::get('/create', [SlaContractController::class, 'create'])
                ->name('create');

            // Store  – POST /carrier/rate-card/sla-contract
            Route::post('/', [SlaContractController::class, 'store'])
                ->name('store');

            // Show   – GET /carrier/rate-card/sla-contract/{slaContract}
            Route::get('/{slaContract}', [SlaContractController::class, 'show'])
                ->name('show');

            // Edit form – GET /carrier/rate-card/sla-contract/{slaContract}/edit
            Route::get('/{slaContract}/edit', [SlaContractController::class, 'edit'])
                ->name('edit');

            // Update – PUT /carrier/rate-card/sla-contract/{slaContract}
            Route::put('/{slaContract}', [SlaContractController::class, 'update'])
                ->name('update');

            // Delete – DELETE /carrier/rate-card/sla-contract/{slaContract}
            Route::delete('/{slaContract}', [SlaContractController::class, 'destroy'])
                ->name('destroy');
        });
    Route::view('/carrier/rate-card/lane-management', 'pages.products.logistics.carriers.lane-management.index')->name('carriers.lane-management.index');
    // });

    // Maersk API Integration
    Route::controller(App\Http\Controllers\Logistics\MaerskController::class)
        ->prefix('maersk')
        ->name('maersk.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/settings', 'settings')->name('settings');
            Route::post('/settings', 'saveSettings')->name('settings.save');
            Route::post('/settings/test', 'testConnection')->name('settings.test');
            Route::match(['get', 'post'], '/track-trace', 'trackTrace')->name('track-trace');
            Route::get('/commodities', 'commodities')->name('commodities');
            Route::get('/deadlines', 'deadlines')->name('deadlines');
            Route::get('/vgm', 'vgm')->name('vgm');
            Route::post('/vgm/submit', 'submitVgm')->name('vgm.submit');
            Route::get('/invoices', 'invoices')->name('invoices');
            Route::get('/invoices/{invoiceNo}/detail', 'invoiceDetail')->name('invoices.detail');
            Route::get('/credits', 'credits')->name('credits');
            Route::get('/demurrage', 'demurrage')->name('demurrage');
            Route::get('/bill-of-lading', 'billOfLading')->name('bill-of-lading');
        });
});
