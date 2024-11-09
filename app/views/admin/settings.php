<!-- views/admin/settings.php -->
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-medium">Site Settings</h2>
    </div>
    <div class="p-6">
        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Settings -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Site Name</label>
                        <input type="text" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contact Email</label>
                        <input type="email" name="contact_email" value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Support Phone</label>
                        <input type="text" name="support_phone" value="<?= htmlspecialchars($settings['support_phone'] ?? '') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea name="address" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        ><?= htmlspecialchars($settings['address'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Business Settings -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Currency</label>
                        <select name="currency" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <?php
                            $currencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD'];
                            foreach ($currencies as $currency) {
                                $selected = ($settings['currency'] ?? 'USD') === $currency ? 'selected' : '';
                                echo "<option value=\"{$currency}\" {$selected}>{$currency}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tax Rate (%)</label>
                        <input type="number" name="tax_rate" step="0.01" min="0" max="100"
                               value="<?= htmlspecialchars($settings['tax_rate'] ?? '0') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Shipping Fee</label>
                        <input type="number" name="shipping_fee" step="0.01" min="0"value="<?= htmlspecialchars($settings['shipping_fee'] ?? '0') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Minimum Order Amount</label>
                        <input type="number" name="min_order_amount" step="0.01" min="0"
                               value="<?= htmlspecialchars($settings['min_order_amount'] ?? '0') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- System Settings -->
            <div class="space-y-4 border-t pt-6">
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="maintenance_mode" 
                               <?= ($settings['maintenance_mode'] ?? false) ? 'checked' : '' ?>
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2">Enable Maintenance Mode</span>
                    </label>
                    <p class="text-sm text-gray-500 mt-1">When enabled, only administrators can access the site.</p>
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="allow_registrations"
                               <?= ($settings['allow_registrations'] ?? true) ? 'checked' : '' ?>
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2">Allow New User Registrations</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>