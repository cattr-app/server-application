import axios from 'axios';
import httpInterceptor from '@/helpers/httpInterceptor';

axios.defaults.baseURL = `${window.location.origin}/api/`;
axios.defaults.headers.common['X-REQUESTED-WITH'] = 'XMLHttpRequest';
axios.defaults.headers.common['X-CATTR-CLIENT'] = window.location.host;
axios.defaults.headers.common['X-CATTR-VERSION'] = process.env.MIX_APP_VERSION;

httpInterceptor.setup();

export default axios;
