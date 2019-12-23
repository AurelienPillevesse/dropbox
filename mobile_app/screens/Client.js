import React, { Component, PropTypes } from 'react';
import {
    AsyncStorage,
} from 'react-native';
import RNFetchBlob from 'react-native-fetch-blob';
import { NavigationActions } from 'react-navigation';

class Client {

    /*
     * Constructor
     */
    constructor() {
        // this.domain = 'https://192.168.0.20'
        this.domain = 'http://192.168.43.219:8000'
        this.URL = this.domain + '/api'
    }

    /*
     * Method that return domain
     */
    getDomain() {
        return this.domain;
    }

    /*
     * Method that return domain + '/api'
     */
    getUrl() {
        return this.URL;
    }

    /*
     * Method that call api to get all in a folder
     */
    getAllFolder(callback, folder_id = null) {
        path = '/folder'
        if(folder_id != null) {
            path += '/' + folder_id
        }

        AsyncStorage.getItem('supfiles_user_token')
        .then((token) => {
            fetch(this.URL + path, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    token: token,
                }),
            })
            .then(response => {
                const statusCode = response.status;
                const data = response.json();
                return Promise.all([data, statusCode]).then(res => ({
                    data: res[0],
                    statusCode: res[1]
                }));
            })
            .then(res => {
                if (res.data && typeof res.data !== "object") {
                    res.data = JSON.parse(res.data)
                }
                callback(res.data, res.statusCode)
            })
            .catch((error) => {
                console.error(error);
            });
        })
    }

    /*
     * Method that call api to rename a folder
     */
    renameFolder(callback, folder_id, name) {
        AsyncStorage.getItem('supfiles_user_token')
        .then((token) => {
            fetch(this.URL + '/folder/rename/' + folder_id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    token: token,
                    name: name
                }),
            })
            .then(response => {
                const statusCode = response.status;
                const data = response.json();
                return Promise.all([data, statusCode]).then(res => ({
                    data: res[0],
                    statusCode: res[1]
                }));
            })
            .then(res => {
                if (res.data && typeof res.data !== "object") {
                    res.data = JSON.parse(res.data)
                }
                callback(res.data, res.statusCode)
            })
            .catch((error) => {
                console.error(error);
            });
        })
    }

    /*
     * Method that call api to delete a folder
     */
    deleteFolder(callback, folder_id) {
        AsyncStorage.getItem('supfiles_user_token')
        .then((token) => {
            fetch(this.URL + '/folder/delete/' + folder_id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    token: token
                }),
            })
            .then(response => {
                const statusCode = response.status;
                const data = response.json();
                return Promise.all([data, statusCode]).then(res => ({
                    data: res[0],
                    statusCode: res[1]
                }));
            })
            .then(res => {
                callback(res.statusCode == 200 ? true : false, res.data, res.statusCode)
            })
            .catch((error) => {
                console.error(error);
            });
        })
    }

    /*
     * Method that call api to create a folder
     */
    createFolder(callback, name, folder_id = null) {
        path = '/folder/add'
        if(folder_id != null) {
            path += '/' + folder_id
        }

        AsyncStorage.getItem('supfiles_user_token')
        .then((token) => {
            fetch(this.URL + path, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    token: token,
                    name: name
                }),
            })
            .then(response => {
                const statusCode = response.status;
                const data = response.json();
                return Promise.all([data, statusCode]).then(res => ({
                    data: res[0],
                    statusCode: res[1]
                }));
            })
            .then(res => {
                if (res.data && typeof res.data !== "object") {
                    res.data = JSON.parse(res.data)
                }
                callback(res.data, res.statusCode)
            })
            .catch((error) => {
                console.error(error);
            });
        })
    }

    /*
     * Method that call api to rename a file
     */
    renameFile(callback, file_id, name) {
        AsyncStorage.getItem('supfiles_user_token')
        .then((token) => {
            fetch(this.URL + '/file/rename/' + file_id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    token: token,
                    name: name
                }),
            })
            .then(response => {
                const statusCode = response.status;
                const data = response.json();
                return Promise.all([data, statusCode]).then(res => ({
                    data: res[0],
                    statusCode: res[1]
                }));
            })
            .then(res => {
                if (res.data && typeof res.data !== "object") {
                    res.data = JSON.parse(res.data)
                }
                callback(res.data, res.statusCode)
            })
            .catch((error) => {
                console.error(error);
            });
        })
    }

    /*
     * Method that call api to delete a file
     */
    deleteFile(callback, file_id) {
        AsyncStorage.getItem('supfiles_user_token')
        .then((token) => {
            fetch(this.URL + '/file/delete/' + file_id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    token: token
                }),
            })
            .then(response => {
                const statusCode = response.status;
                const data = response.json();
                return Promise.all([data, statusCode]).then(res => ({
                    data: res[0],
                    statusCode: res[1]
                }));
            })
            .then(res => {
                callback(res.statusCode == 200 ? true : false, res.data, res.statusCode)
            })
            .catch((error) => {
                console.error(error);
            });
        })
    }

    /*
     * Method that call api to download a file
     */
    downloadFile(callback, file_id, file_name, file_extension, mime_type) {
        AsyncStorage.getItem('supfiles_user_token')
        .then((token) => {
            RNFetchBlob
            .config({
                fileCache: true,
                path : RNFetchBlob.fs.dirs.DownloadDir + '/' + file_name + '.' + file_extension,
                addAdnroidDownloads : {
                    useDownloadManager : true,
                    notification : false,
                    mime : mime_type,
                    description : 'File downloaded by download manager.'
                }
            })
            .fetch(
                'POST',
                this.URL + '/file/download/' + file_id, {
                    'Content-Type': 'application/json',
                },
                JSON.stringify({
                    token: token
                }),
            )
            .then(res => {
                callback(res.info().status == 200 ? true : false, res.path() != null ? res.path() : null, res.info().status)
            })
            .catch((error) => {
                console.error(error);
            });
        })
    }

    /*
     * Method that call api to get a file
     */
    getFile(callback, file_id) {
        AsyncStorage.getItem('supfiles_user_token')
        .then((token) => {
            fetch(this.URL + '/file/' + file_id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    token: token
                }),
            })
            .then(response => {
                const statusCode = response.status;
                const data = response.json();
                return Promise.all([data, statusCode]).then(res => ({
                    data: res[0],
                    statusCode: res[1]
                }));
            })
            .then(res => {
                if (res.data && typeof res.data !== "object") {
                    res.data = JSON.parse(res.data)
                }

                if (res.data.file && typeof res.data.file !== "object") {
                    res.data.file = JSON.parse(res.data.file)
                }
                callback(res.data, res.statusCode)
            })
            .catch((error) => {
                console.error(error);
            });
        })
    }

    /*
     * Method that call api to upload a file
     */
    uploadFile(callback, file, folder_id = null) {
        path = '/file/add'
        if(folder_id != null) {
            path += '/' + folder_id
        }

        AsyncStorage.getItem('supfiles_user_token')
        .then((token) => {
            const data = new FormData();
            data.append('token', token);
            data.append('file', {
                uri: file.uri,
                type: file.type,
                name: file.fileName,
            });

            fetch(this.URL + path, {
                method: 'POST',
                body: data
            })
            .then(response => {
                const statusCode = response.status;
                const data = response.json();
                return Promise.all([data, statusCode]).then(res => ({
                    data: res[0],
                    statusCode: res[1]
                }));
            })
            .then(res => {
                if (res.data && typeof res.data !== "object") {
                    res.data = JSON.parse(res.data)
                }

                if (res.data.file && typeof res.data.file !== "object") {
                    res.data.file = JSON.parse(res.data.file)
                }
                callback(res.data, res.statusCode)
            })
            .catch((error) => {
                console.error(error);
            });
        })
    }

    /*
     * Method that call api to share a folder
     */
    shareFolder(callback, folder_id) {
        AsyncStorage.getItem('supfiles_user_token')
        .then((token) => {
            fetch(this.URL + '/folder/share/' + folder_id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    token: token,
                }),
            })
            .then(response => {
                const statusCode = response.status;
                console.log(response)
                const data = response.json();
                return Promise.all([data, statusCode]).then(res => ({
                    data: res[0],
                    statusCode: res[1]
                }));
            })
            .then(res => {
                console.log(res)
                console.log(res.data)
                if (res.data && typeof res.data !== "object") {
                    res.data = JSON.parse(res.data)
                }

                callback(res.data, res.statusCode)
            })
            .catch((error) => {
                console.error(error);
            });
        })
    }

    /*
     * Method that call api to logout
     */
    logout(callback = null) {
        AsyncStorage.getItem('supfiles_user_token')
        .then((token) => {
            fetch(this.URL + '/logout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    token: token,
                }),
            })
            .then(response => {
                const statusCode = response.status;
                const data = response.json();
                return Promise.all([data, statusCode]).then(res => ({
                    data: res[0],
                    statusCode: res[1]
                }));
            })
            .then(res => {
                if (res.data && typeof res.data !== "object") {
                    res.data = JSON.parse(res.data)
                }

                if(callback) {
                    callback(res.data, res.statusCode)
                }
            })
            .catch((error) => {
                console.error(error);
            });
        })
    }
}

const clientInstance = new Client();
export default clientInstance;
