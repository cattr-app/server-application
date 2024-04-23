<template>
    <div class="desktop-key content-wrapper">
        <div class="container">
            <div class="at-container crud__content">
                <div class="at-container__inner">
                    <h2 v-t="'auth.desktop.header'" />
                </div>
                <at-steps :current="current" class="col-lg-offset-7">
                    <at-step
                        :icon="transferStatus === 'process' ? 'icon-lock' : undefined"
                        :status="transferStatus"
                        :description="$t('auth.desktop.step1')"
                        :title="$t('auth.desktop.step', { n: 1 })"
                    />
                    <at-step
                        :icon="openStatus !== 'finish' && openStatus !== 'error' ? 'icon-monitor' : undefined"
                        :status="openStatus"
                        :description="$t('auth.desktop.step2')"
                        :title="$t('auth.desktop.step', { n: 2 })"
                    />
                </at-steps>
                <div class="row">
                    <div v-if="transferStatus === 'finish' && openStatus === 'error'" class="col-10 col-offset-7">
                        <p v-t="'auth.desktop.error'" />
                        <i18n path="auth.desktop.download" tag="p">
                            <a v-t="'auth.desktop.download_button'" href="https://cattr.app/desktop" target="_blank" />
                        </i18n>
                    </div>
                    <div class="col-6 col-offset-9">
                        <at-button
                            :type="
                                transferStatus === 'finish'
                                    ? openStatus === 'finish'
                                        ? 'success'
                                        : 'primary'
                                    : 'error'
                            "
                            @click="click"
                        >
                            {{
                                transferStatus === 'error' || openStatus === 'error'
                                    ? $t('auth.desktop.retry')
                                    : transferStatus === 'finish' && openStatus !== 'finish'
                                      ? $t('auth.desktop.open')
                                      : transferStatus === 'finish' && openStatus === 'finish'
                                        ? $t('auth.desktop.finish')
                                        : $t('auth.desktop.cancel')
                            }}
                        </at-button>
                    </div>
                    <!-- /.row -->
                </div>
                <!-- /.at-container__inner -->
            </div>
            <!-- /.at-container -->
        </div>
        <!-- /.container -->
    </div>
    <!-- /.content-wrapper -->
</template>

<script>
    import axios from 'axios';

    export default {
        name: 'DesktopLogin',
        async mounted() {
            this.isLoading = true;
            try {
                const { data } = await axios.get('auth/desktop-key');

                this.current += 1;
                this.transferStatus = 'finish';

                this.token = data.access_token;
            } catch ({ response }) {
                this.transferStatus = 'error';

                if (process.env.NODE_ENV === 'development') {
                    console.warn(response ? response : 'Issuing desktop key has been canceled');
                }
            }

            this.isLoading = false;
        },
        data() {
            return {
                current: 0,
                transferStatus: 'process',
                openStatus: 'wait',
                token: null,
            };
        },
        methods: {
            click() {
                if (this.transferStatus === 'error' || this.openStatus === 'error') {
                    window.location.reload();
                } else if (
                    this.transferStatus === 'finish' &&
                    (this.openStatus === 'wait' || this.openStatus === 'process')
                ) {
                    this.current += 1;
                    this.openStatus = 'process';

                    const client = window.open(
                        `cattr://authenticate?url=${encodeURIComponent(
                            process.env.VUE_APP_API_URL || `${window.location.origin}/api`,
                        )}&token=${this.token}`,
                    );

                    const failFunction = () => {
                        if (this.openStatus !== 'finish') {
                            this.openStatus = 'error';
                        }
                    };

                    const timeout = setTimeout(failFunction, 1);

                    client.onblur = () => {
                        clearTimeout(timeout);
                        if (this.openStatus !== 'error') {
                            this.openStatus = 'finish';
                        }
                    };

                    client.onunload = client.onclose = () => {
                        clearTimeout(timeout);
                        failFunction();
                    };
                } else {
                    window.history.back();
                }
            },
        },
    };
</script>

<style lang="scss" scoped>
    .desktop-key {
        text-align: center;

        .at-steps {
            text-align: left;
        }

        .row {
            margin-top: 20px;

            & > .col-10 {
                margin-bottom: 10px;
            }
        }
    }
</style>
