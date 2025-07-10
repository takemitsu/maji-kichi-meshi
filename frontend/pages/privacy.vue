<template>
    <div class="mx-auto max-w-4xl py-8 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-sm">
            <div class="px-6 py-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-8">プライバシーポリシー</h1>

                <div class="prose prose-lg max-w-none">
                    <p class="text-gray-600 mb-6">最終更新日: {{ new Date().toLocaleDateString('ja-JP') }}</p>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">1. 基本方針</h2>
                        <p class="text-gray-700 mb-4">
                            マジキチメシ（以下「本サービス」）は、個人が開発・運営する非営利のWebアプリケーションです。
                            ユーザーの個人情報保護に努めますが、個人開発プロジェクトのため、完全なセキュリティは保証できません。
                            ご利用は自己責任でお願いします。
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">2. 収集する情報</h2>
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">2.1 OAuth認証情報</h3>
                                <ul class="list-disc list-inside text-gray-700 space-y-1">
                                    <li>GoogleのOAuth認証で取得する公開情報</li>
                                    <li>ユーザーID、表示名、メールアドレス、プロフィール画像URL等</li>
                                </ul>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">2.2 サービス利用情報</h3>
                                <ul class="list-disc list-inside text-gray-700 space-y-1">
                                    <li>投稿したレビュー内容、評価、コメント</li>
                                    <li>アップロードした画像ファイル</li>
                                    <li>アクセスログ、利用状況データ</li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">3. 情報の利用目的</h2>
                        <ul class="list-disc list-inside text-gray-700 space-y-2">
                            <li>本サービスの提供・運営</li>
                            <li>ユーザー認証・識別</li>
                            <li>レビュー投稿機能の提供</li>
                            <li>サービス改善のための分析</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">4. 情報の共有・公開</h2>
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">4.1 公開される情報</h3>
                                <ul class="list-disc list-inside text-gray-700 space-y-1">
                                    <li>投稿したレビュー内容は他のユーザーに公開されます</li>
                                    <li>ユーザー名、評価、コメント、画像が含まれます</li>
                                </ul>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">4.2 第三者への提供</h3>
                                <p class="text-gray-700">
                                    法令に基づく場合を除き、ユーザーの同意なく第三者に個人情報を提供することはありません。
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">5. 情報の管理・削除</h2>
                        <ul class="list-disc list-inside text-gray-700 space-y-2">
                            <li>アカウント削除時にユーザー情報は削除されますが、投稿コンテンツは残る場合があります</li>
                            <li>個人開発のため、完全なデータ削除は保証できません</li>
                            <li>データの復旧・回復サービスは提供していません</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">6. セキュリティ</h2>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <p class="text-yellow-800 font-medium mb-2">⚠️ 重要な注意事項</p>
                            <ul class="list-disc list-inside text-yellow-700 space-y-1">
                                <li>本サービスは個人開発プロジェクトのため、企業レベルのセキュリティは保証できません</li>
                                <li>データの漏洩、消失、改ざん等のリスクがあります</li>
                                <li>機密情報や重要な個人情報の投稿は避けてください</li>
                                <li>利用は完全に自己責任となります</li>
                            </ul>
                        </div>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">7. 外部サービス</h2>
                        <p class="text-gray-700 mb-4">本サービスは以下の外部サービスを利用しています：</p>
                        <ul class="list-disc list-inside text-gray-700 space-y-1">
                            <li>Google Places API（店舗情報取得）</li>
                            <li>Google Maps API（地図表示）</li>
                            <li>各種OAuth認証プロバイダー</li>
                        </ul>
                        <p class="text-gray-700 mt-4">これらのサービスにはそれぞれのプライバシーポリシーが適用されます。</p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">8. 免責事項</h2>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <ul class="list-disc list-inside text-red-700 space-y-1">
                                <li>本サービスの利用により生じた損害について、開発者は一切の責任を負いません</li>
                                <li>サービスの継続性、安定性、セキュリティは保証されません</li>
                                <li>予告なくサービスが終了する可能性があります</li>
                                <li>データのバックアップは各自で行ってください</li>
                            </ul>
                        </div>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">9. 本ポリシーの変更</h2>
                        <p class="text-gray-700">
                            本ポリシーは予告なく変更される場合があります。 重要な変更がある場合は、サービス内で通知します。
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">10. お問い合わせ</h2>
                        <p class="text-gray-700">
                            本ポリシーに関するお問い合わせは、GitHubのIssuesまたはメール等でお願いします。
                            ただし、個人開発のため迅速な対応は保証できません。
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
// メタデータ設定
useHead({
    title: 'プライバシーポリシー - マジキチメシ',
    meta: [
        { name: 'description', content: 'マジキチメシのプライバシーポリシー' },
        { name: 'robots', content: 'index, follow' },
    ],
})
</script>
