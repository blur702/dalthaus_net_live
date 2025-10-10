<?php
/**
 * @var array $stats
 * @var string $csrf_token
 */
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="/admin/media" class="text-blue-600 hover:text-blue-800">&larr; Back to Media Uploads</a>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
            <h1 class="text-2xl font-bold text-gray-800">Media Upload Statistics</h1>
        </div>

        <div class="p-6 space-y-8">
            <!-- Overall Statistics -->
            <div>
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Overall Statistics</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-blue-50 p-6 rounded-lg border-2 border-blue-200">
                        <div class="text-sm text-blue-600 font-medium mb-1">Total Uploads</div>
                        <div class="text-3xl font-bold text-blue-900">
                            <?= number_format($stats['totals']['total_uploads'] ?? 0) ?>
                        </div>
                    </div>

                    <div class="bg-purple-50 p-6 rounded-lg border-2 border-purple-200">
                        <div class="text-sm text-purple-600 font-medium mb-1">Total Storage</div>
                        <div class="text-3xl font-bold text-purple-900">
                            <?= round(($stats['totals']['total_size'] ?? 0) / 1024 / 1024, 2) ?> MB
                        </div>
                        <div class="text-xs text-purple-600 mt-1">
                            <?= number_format($stats['totals']['total_size'] ?? 0) ?> bytes
                        </div>
                    </div>

                    <div class="bg-green-50 p-6 rounded-lg border-2 border-green-200">
                        <div class="text-sm text-green-600 font-medium mb-1">Used in Content</div>
                        <div class="text-3xl font-bold text-green-900">
                            <?= number_format($stats['totals']['used_uploads'] ?? 0) ?>
                        </div>
                        <div class="text-xs text-green-600 mt-1">
                            <?php
                            $total = $stats['totals']['total_uploads'] ?? 0;
                            $used = $stats['totals']['used_uploads'] ?? 0;
                            $percentage = $total > 0 ? round(($used / $total) * 100, 1) : 0;
                            ?>
                            <?= $percentage ?>% of total
                        </div>
                    </div>

                    <div class="bg-orange-50 p-6 rounded-lg border-2 border-orange-200">
                        <div class="text-sm text-orange-600 font-medium mb-1">Unused</div>
                        <div class="text-3xl font-bold text-orange-900">
                            <?= number_format($stats['totals']['unused_uploads'] ?? 0) ?>
                        </div>
                        <div class="text-xs text-orange-600 mt-1">
                            <?php
                            $unused = $stats['totals']['unused_uploads'] ?? 0;
                            $unusedPercentage = $total > 0 ? round(($unused / $total) * 100, 1) : 0;
                            ?>
                            <?= $unusedPercentage ?>% of total
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics by Upload Type -->
            <div>
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Statistics by Upload Type</h2>
                <?php if (empty($stats['by_type'])): ?>
                    <p class="text-gray-600">No upload type statistics available.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Upload Type
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total Count
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total Size
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Used
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Unused
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Usage Rate
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($stats['by_type'] as $typeStat): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-xs rounded-full font-medium
                                                <?= $typeStat['upload_type'] === 'tinymce' ? 'bg-blue-100 text-blue-800' :
                                                    ($typeStat['upload_type'] === 'dual_display' ? 'bg-green-100 text-green-800' :
                                                    ($typeStat['upload_type'] === 'dual_modal' ? 'bg-purple-100 text-purple-800' :
                                                    'bg-gray-100 text-gray-800')) ?>">
                                                <?= $this->escape($typeStat['upload_type']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?= number_format($typeStat['count_by_type'] ?? 0) ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?= round(($typeStat['total_size'] ?? 0) / 1024 / 1024, 2) ?> MB
                                        </td>
                                        <td class="px-6 py-4 text-sm text-green-600 font-medium">
                                            <?= number_format($typeStat['used_uploads'] ?? 0) ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-orange-600 font-medium">
                                            <?= number_format($typeStat['unused_uploads'] ?? 0) ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php
                                            $typeTotal = $typeStat['count_by_type'] ?? 0;
                                            $typeUsed = $typeStat['used_uploads'] ?? 0;
                                            $typePercentage = $typeTotal > 0 ? round(($typeUsed / $typeTotal) * 100, 1) : 0;
                                            ?>
                                            <div class="flex items-center">
                                                <div class="flex-1 bg-gray-200 rounded-full h-2 mr-2">
                                                    <div class="bg-green-600 h-2 rounded-full" style="width: <?= $typePercentage ?>%"></div>
                                                </div>
                                                <span class="text-xs font-medium"><?= $typePercentage ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Visual Chart -->
            <div>
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Usage Distribution</h2>
                <div class="bg-gray-50 p-6 rounded-lg">
                    <div class="flex items-center justify-center space-x-8">
                        <?php
                        $totalUploads = $stats['totals']['total_uploads'] ?? 0;
                        $usedUploads = $stats['totals']['used_uploads'] ?? 0;
                        $unusedUploads = $stats['totals']['unused_uploads'] ?? 0;

                        $usedPercent = $totalUploads > 0 ? round(($usedUploads / $totalUploads) * 100, 1) : 0;
                        $unusedPercent = $totalUploads > 0 ? round(($unusedUploads / $totalUploads) * 100, 1) : 0;
                        ?>

                        <div class="text-center">
                            <div class="relative inline-flex items-center justify-center">
                                <svg class="w-32 h-32 transform -rotate-90">
                                    <circle cx="64" cy="64" r="52" stroke="#e5e7eb" stroke-width="16" fill="none"/>
                                    <circle cx="64" cy="64" r="52" stroke="#10b981" stroke-width="16" fill="none"
                                            stroke-dasharray="<?= 2 * 3.14159 * 52 ?>"
                                            stroke-dashoffset="<?= 2 * 3.14159 * 52 * (1 - $usedPercent / 100) ?>"
                                            class="transition-all duration-500"/>
                                </svg>
                                <div class="absolute text-2xl font-bold text-gray-800">
                                    <?= $usedPercent ?>%
                                </div>
                            </div>
                            <div class="mt-2 text-sm font-medium text-gray-600">Used in Content</div>
                            <div class="text-2xl font-bold text-green-600"><?= number_format($usedUploads) ?></div>
                        </div>

                        <div class="text-center">
                            <div class="relative inline-flex items-center justify-center">
                                <svg class="w-32 h-32 transform -rotate-90">
                                    <circle cx="64" cy="64" r="52" stroke="#e5e7eb" stroke-width="16" fill="none"/>
                                    <circle cx="64" cy="64" r="52" stroke="#f97316" stroke-width="16" fill="none"
                                            stroke-dasharray="<?= 2 * 3.14159 * 52 ?>"
                                            stroke-dashoffset="<?= 2 * 3.14159 * 52 * (1 - $unusedPercent / 100) ?>"
                                            class="transition-all duration-500"/>
                                </svg>
                                <div class="absolute text-2xl font-bold text-gray-800">
                                    <?= $unusedPercent ?>%
                                </div>
                            </div>
                            <div class="mt-2 text-sm font-medium text-gray-600">Unused</div>
                            <div class="text-2xl font-bold text-orange-600"><?= number_format($unusedUploads) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
