<template>
    <div class="mx-auto max-w-4xl py-8 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-sm">
            <div class="px-6 py-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-8">利用規約</h1>

                <div class="prose prose-lg max-w-none">
                    <p class="text-gray-600 mb-6">最終更新日: {{ new Date().toLocaleDateString('ja-JP') }}</p>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">1. 基本事項</h2>
                        <div class="space-y-4">
                            <p class="text-gray-700">
                                マジキチメシ（以下「本サービス」）は、個人が趣味で開発・運営する非営利のWebアプリケーションです。
                                本利用規約（以下「本規約」）は、本サービスの利用に関する条件を定めるものです。
                            </p>
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <p class="text-red-800 font-medium mb-2">⚠️ 重要</p>
                                <p class="text-red-700">
                                    本サービスは個人開発プロジェクトのため、利用は完全に自己責任となります。
                                    開発者は本サービスに関して一切の責任を負いません。
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">2. サービス概要</h2>
                        <div class="space-y-4">
                            <p class="text-gray-700 mb-4">本サービスは以下の機能を提供します：</p>
                            <ul class="list-disc list-inside text-gray-700 space-y-1">
                                <li>吉祥寺地域の店舗情報の検索・閲覧</li>
                                <li>店舗レビューの投稿・閲覧</li>
                                <li>個人的な店舗ランキングの作成・共有</li>
                                <li>画像アップロード機能</li>
                            </ul>
                            <p class="text-gray-700 mt-4">ただし、これらの機能の継続的な提供は保証されません。</p>
                        </div>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">3. 利用資格・アカウント</h2>
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">3.1 利用資格</h3>
                                <ul class="list-disc list-inside text-gray-700 space-y-1">
                                    <li>本規約に同意した方</li>
                                    <li>GoogleのOAuth認証が可能な方</li>
                                    <li>法的に有効な契約を締結する能力を有する方</li>
                                </ul>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">3.2 アカウント管理</h3>
                                <ul class="list-disc list-inside text-gray-700 space-y-1">
                                    <li>アカウント情報の管理は各自の責任で行ってください</li>
                                    <li>アカウントの不正利用による損害は利用者の責任となります</li>
                                    <li>複数アカウントの作成は推奨しません</li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">4. 禁止事項</h2>
                        <p class="text-gray-700 mb-4">本サービスの利用において、以下の行為を禁止します：</p>
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">4.1 投稿コンテンツに関する禁止事項</h3>
                                <ul class="list-disc list-inside text-gray-700 space-y-1">
                                    <li>虚偽の情報、誤解を招く情報の投稿</li>
                                    <li>他人の名誉を毀損する内容</li>
                                    <li>差別的、攻撃的、不適切な内容</li>
                                    <li>著作権、商標権等の知的財産権を侵害する内容</li>
                                    <li>個人情報、機密情報の投稿</li>
                                    <li>商業的な宣伝、勧誘、スパム行為</li>
                                </ul>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">4.2 技術的な禁止事項</h3>
                                <ul class="list-disc list-inside text-gray-700 space-y-1">
                                    <li>システムへの不正アクセス、攻撃行為</li>
                                    <li>過度なアクセス、サーバーに負荷をかける行為</li>
                                    <li>自動化ツールの使用（事前承認がない限り）</li>
                                    <li>リバースエンジニアリング、改ざん行為</li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">5. 投稿コンテンツ</h2>
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">5.1 投稿内容の責任</h3>
                                <ul class="list-disc list-inside text-gray-700 space-y-1">
                                    <li>投稿内容については投稿者が全責任を負います</li>
                                    <li>第三者との紛争が生じた場合は投稿者が解決してください</li>
                                    <li>開発者は投稿内容について一切の責任を負いません</li>
                                </ul>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">5.2 投稿内容の取り扱い</h3>
                                <ul class="list-disc list-inside text-gray-700 space-y-1">
                                    <li>投稿内容は他のユーザーに公開されます</li>
                                    <li>不適切な投稿は予告なく削除する場合があります</li>
                                    <li>投稿の修正・削除機能は保証されません</li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">6. サービスの提供・変更・終了</h2>
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">6.1 サービスの提供</h3>
                                <ul class="list-disc list-inside text-gray-700 space-y-1">
                                    <li>本サービスは現状有姿で提供されます</li>
                                    <li>継続的な提供、安定性、可用性は保証されません</li>
                                    <li>メンテナンス、障害等により利用できない場合があります</li>
                                </ul>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">6.2 サービスの変更・終了</h3>
                                <ul class="list-disc list-inside text-gray-700 space-y-1">
                                    <li>予告なくサービス内容を変更または終了する場合があります</li>
                                    <li>サービス終了時のデータ移行・保存は保証されません</li>
                                    <li>重要なデータは各自でバックアップを取ってください</li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">7. 免責事項</h2>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 md:p-6">
                            <h3 class="text-lg font-medium text-red-900 mb-4">開発者の免責</h3>
                            <ul class="list-disc list-inside text-red-700 space-y-2">
                                <li>
                                    <strong>本サービスの利用により生じた一切の損害について、開発者は責任を負いません</strong>
                                </li>
                                <li>データの消失、漏洩、改ざん等による損害</li>
                                <li>投稿内容に起因する第三者との紛争</li>
                                <li>システム障害、セキュリティ事故による損害</li>
                                <li>サービス終了による機会損失</li>
                                <li>その他本サービスに関連する直接・間接の損害</li>
                            </ul>
                            <p class="text-red-700 mt-4 font-medium">利用者は自己の責任と判断でサービスを利用してください。</p>
                        </div>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">8. 利用停止・アカウント削除</h2>
                        <div class="space-y-4">
                            <p class="text-gray-700">以下の場合、予告なく利用を停止またはアカウントを削除する場合があります：</p>
                            <ul class="list-disc list-inside text-gray-700 space-y-1">
                                <li>本規約に違反した場合</li>
                                <li>不正な利用が確認された場合</li>
                                <li>システムに重大な影響を与える行為をした場合</li>
                                <li>その他開発者が不適切と判断した場合</li>
                            </ul>
                            <p class="text-gray-700 mt-4">
                                利用停止・アカウント削除による損害についても開発者は責任を負いません。
                            </p>
                        </div>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">9. 個人情報・プライバシー</h2>
                        <p class="text-gray-700">
                            個人情報の取り扱いについては、
                            <NuxtLink to="/privacy" class="text-blue-600 hover:text-blue-800">プライバシーポリシー</NuxtLink>
                            をご確認ください。
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">10. 準拠法・管轄</h2>
                        <p class="text-gray-700">
                            本規約は日本法に準拠し、本サービスに関する紛争については、
                            開発者の居住地を管轄する裁判所を第一審の専属的合意管轄裁判所とします。
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">11. 規約の変更</h2>
                        <p class="text-gray-700">
                            本規約は予告なく変更される場合があります。 重要な変更がある場合は、サービス内で通知します。
                            変更後も利用を継続した場合、変更に同意したものとみなします。
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">12. お問い合わせ</h2>
                        <p class="text-gray-700">
                            本規約に関するお問い合わせは、GitHubのIssuesまたはメール等でお願いします。
                            ただし、個人開発のため迅速な対応は保証できません。
                        </p>
                    </section>

                    <div class="mt-12 p-4 md:p-6 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-600 text-center">
                            本サービスは個人開発プロジェクトです。
                            <br />
                            利用は完全に自己責任でお願いします。
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
// メタデータ設定
useHead({
    title: '利用規約 - マジキチメシ',
    meta: [
        { name: 'description', content: 'マジキチメシの利用規約' },
        { name: 'robots', content: 'index, follow' },
    ],
})
</script>
