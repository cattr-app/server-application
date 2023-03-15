import axios from 'axios';

export default class AuthService {
    resetPasswordRequest(data) {
        return axios.post('auth/password/reset/request', data);
    }

    resetPasswordValidateToken(data) {
        return axios.post('auth/password/reset/validate', data);
    }

    resetPasswordProcess(data) {
        return axios.post('auth/password/reset/process', data);
    }
}
