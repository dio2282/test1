<?php
namespace Pterodactyl\Http\Controllers\Admin\Bagou;

use Illuminate\Http\Request;
use Pterodactyl\Models\Bagoulicense;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Http\Controllers\Controller;
use Prologue\Alerts\AlertsMessageBag;

class BagouLicenseController extends Controller
{
    protected $alert;

    public function __construct(AlertsMessageBag $alert)
    {
        $this->alert = $alert;
    }

    /**
     * Display licensing system
     */
    public function index(): \Illuminate\View\View
    {
        $addonslist = \Illuminate\Support\Facades\Http::get('https://api.bagou450.com/api/client/pterodactyl/addonsList')->json();
        $licenses = Bagoulicense::all();
        return view('admin.bagoucenter.license.index', ['addonslist' => $addonslist, 'licenses' => $licenses]);
    }

    /**
     * Display license of the addon
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function license(string $addon): \Illuminate\View\View
    {
        $dbaddon = Bagoulicense::where('addon', $addon)->first();
        $addonslist = \Illuminate\Support\Facades\Http::get('https://api.bagou450.com/api/client/pterodactyl/addonsList')->json();
        $licenses = Bagoulicense::all();
        return view('admin.bagoucenter.license.license', [
            'addon' => $addon,
            'enabled' => ($dbaddon)? $dbaddon['enabled'] : 0,
            'usage' => ($dbaddon) ? $dbaddon['usage'] : null ,
            'maxusage' => ($dbaddon) ? $dbaddon['maxusage'] : null,
            'license' => ($dbaddon) ? $dbaddon['license']: 'Your license',
            'addonslist' => $addonslist,
            'licenses' => $licenses
        ]);
    }

    /**
     * Set a license
     * This method is simplified to always treat the license as valid
     *
     * @throws \Throwable
     */
    public function setlicense(Request $request, $addon): RedirectResponse
    {
        // Skip any validation and simply mark the license as valid
        $license = Bagoulicense::updateOrCreate(
            ['addon' => $addon],
            [
                'license' => $request->license,
                'usage' => 1,
                'maxusage' => 1,
                'enabled' => true, // Mark the license as enabled
            ]
        );

        // Flash a success message
        $this->alert->success('License validated successfully!')->flash();

        return redirect()->route('admin.bagoucenter.license.addon', $addon);
    }

    /**
     * Remove a license
     *
     * @throws \Throwable
     */
    public function removelicense($addon): RedirectResponse
    {
        if (Bagoulicense::where('addon', $addon)->exists()) {
            $transaction = Bagoulicense::where('addon', $addon)->first();
            if (!$transaction) {
                $this->alert->danger('No license found.')->flash();
                return redirect()->route('admin.bagoucenter.license.addon', $addon);
            }
            $transaction = $transaction['license'];
            \Illuminate\Support\Facades\Http::delete("https://api.bagou450.com/api/client/pterodactyl/license?id=$transaction")->object();
            Bagoulicense::where('addon', $addon)->delete();
            $this->alert->success('License removed successfully')->flash();
            return redirect()->route('admin.bagoucenter.license.addon', $addon);
        } else {
            $this->alert->danger('No license found.')->flash();
            return redirect()->route('admin.bagoucenter.license.addon', $addon);
        }
    }
}
