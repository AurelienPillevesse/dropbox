import React, { Component, PropTypes } from 'react';
import {
  StyleSheet,
  Text,
  View,
  TouchableOpacity,
  Image,
  AsyncStorage,
  TextInput,
  Dimensions,
  ScrollView,
  ToastAndroid,
  Modal,
  Animated,
  RefreshControl,
  ActivityIndicator,
  Clipboard,
} from 'react-native';

import ActionButton from '../components/ActionButton';
import Icon from 'react-native-vector-icons/Feather';
import MaterialCommunityIcons from 'react-native-vector-icons/MaterialCommunityIcons';
import { NavigationActions, Header } from 'react-navigation';
import { DocumentPicker, DocumentPickerUtil } from 'react-native-document-picker';
import clientInstance from './Client';

export default class FolderScreen extends Component {

    /*
     * Constructor
     */
    constructor(props){
        super(props);

        var {height, width} = Dimensions.get('window');
        this.state = {
            foldersData: null,
            filesData: null,
            modalVisibleFolder: false,
            modalVisibleFile: false,
            modalVisibleRenameFile: false,
            modalVisibleRenameFolder: false,
            modalVisibleCircleButton: false,
            modalVisibleCreateFolder: false,
            modalVisibleShare: false,
            currentId: null,
            currentName: null,
            name: null,
            shareLink: null,
            actualFolderId: null,
            refreshing: false,
            height: height - (Header.HEIGHT),
            width: width,
        }

        this.afterGetAll = this.afterGetAll.bind(this);
        this.afterRenameFolder = this.afterRenameFolder.bind(this);
        this.afterDeleteFolder = this.afterDeleteFolder.bind(this);
        this.afterCreateFolder = this.afterCreateFolder.bind(this);
        this.afterShareFolder = this.afterShareFolder.bind(this);
        this.afterRenameFile = this.afterRenameFile.bind(this);
        this.afterDeleteFile = this.afterDeleteFile.bind(this);
        this.afterDownloadFile = this.afterDownloadFile.bind(this);
        this.afterFileUpload = this.afterFileUpload.bind(this);
        this.setLoadingStateToTrue = this.setLoadingStateToTrue.bind(this);
    }

    /*
     * Navigations options
     */
    static navigationOptions = ({ navigation, screenProps }) => ({
        headerRight: <TouchableOpacity onPress={() => {
            clientInstance.logout()
            AsyncStorage.removeItem('supfiles_user_token')
            .then(() => {
                const resetAction = NavigationActions.reset({
                  index: 0,
                  actions: [
                    NavigationActions.navigate({ routeName: 'LaunchScreen'})
                  ]
                })
                navigation.dispatch(resetAction)
            })
        }}>
            <Icon name="log-out" size={25} style={{paddingRight: 5}}/>
            <Text style={{paddingRight: 5}}>Log out</Text>
        </TouchableOpacity>,
    });

    /*
     * Method that set refresh to true and get all in folder
     */
    _onRefresh() {
        this.setState({refreshing: true});
        this.getAllFolder();
    }

    /*
     * Get all in the current folder when component is mount
     */
    componentDidMount() {
        this.getAllFolder();
    }

    /*
     * Method that set loading to true
     */
    setLoadingStateToTrue() {
        this.setState({
            refreshing: true,
        })
    }

    /*
     * Method that display toast message
     */
    displayToastMessage(message) {
        ToastAndroid.showWithGravityAndOffset(
            message,
            ToastAndroid.LONG, ToastAndroid.BOTTOM, 25, 200
        );
    }

    /*
     * Method that display message according to the status code
     */
    statusCodeGesture(statusCode, content = null) {
        if(statusCode == 404) {
            this.displayToastMessage("This resource doesn't exists!")
        }

        if(statusCode == 400 || statusCode == 500) {
            if(content && content.errorMessage) {
                this.displayToastMessage(content.errorMessage)
            } else {
                this.displayToastMessage('Oops! An error occured!')
            }
        }

        if(statusCode == 401 || statusCode == 422) {
            AsyncStorage.removeItem('supfiles_user_token')
            .then(() => {
                const resetAction = NavigationActions.reset({
                  index: 0,
                  actions: [
                    NavigationActions.navigate({ routeName: 'LaunchScreen'})
                  ]
                })
                this.props.navigation.dispatch(resetAction)
                this.displayToastMessage("Your session has expired. Sign in again!")
            })
        }
    }

    /*
    Callback methods
    */

    /*
     * Callback when get all in folder and update with new datas
     */
    afterGetAll(responseJson, statusCode) {
        this.statusCodeGesture(statusCode, responseJson)

        if(responseJson.folders && responseJson.files) {
            this.setState({
                refreshing: false,
                foldersData: responseJson.folders ? responseJson.folders : null,
                filesData: responseJson.files ? responseJson.files : null
            })
        } else {
            this.setState({
                refreshing: false,
            })
        }
    }

    /*
     * Callback when folder renamed and update with new datas
     */
    afterRenameFolder(responseJson, statusCode) {
        this.statusCodeGesture(statusCode, responseJson)

        if(responseJson.id && responseJson.name) {
            for (var i = 0; i < this.state.foldersData.length; i++) {
                if(this.state.foldersData[i].id == responseJson.id) {
                    this.state.foldersData[i].name = responseJson.name
                }
            }

            this.setState({
                refreshing: false,
                foldersData: this.state.foldersData,
                modalVisibleRenameFolder: false,
                name: null
            })
        } else {
            this.setState({
                refreshing: false,
                modalVisibleRenameFolder: false,
                name: null
            })
        }
    }

    /*
     * Callback when folder deleted and update with new datas
     */
    afterDeleteFolder(deleted, responseJson, statusCode) {
        this.statusCodeGesture(statusCode)

        if(deleted == true) {
            for (var i = 0; i < this.state.foldersData.length; i++) {
                if(this.state.foldersData[i].id == this.state.currentId) {
                    this.state.foldersData.splice(i, 1);
                }
            }

            this.setState({
                refreshing: false,
                foldersData: this.state.foldersData,
                modalVisibleFolder: false,
            })
        } else {
            this.setState({
                refreshing: false,
                modalVisibleFolder: false,
            })
            this.displayToastMessage('An error occurred while deleting.')
        }
    }

    /*
     * Callback when folder created and update with new datas
     */
    afterCreateFolder(responseJson, statusCode) {
        this.statusCodeGesture(statusCode, responseJson)

        if(responseJson.id && responseJson.name) {
            this.state.foldersData.unshift(responseJson)
        }

        this.setState({
            refreshing: false,
            name: null,
            modalVisibleCreateFolder: false,
        })
    }

    /*
     * Callback when file renamed and update with new datas
     */
    afterRenameFile(responseJson, statusCode) {
        this.statusCodeGesture(statusCode, responseJson)

        if(responseJson.id && responseJson.name) {
            for (var i = 0; i < this.state.filesData.length; i++) {
                if(this.state.filesData[i].id == responseJson.id) {
                    this.state.filesData[i].name = responseJson.name
                }
            }

            this.setState({
                refreshing: false,
                filesData: this.state.filesData,
                modalVisibleRenameFile: false,
                name: null
            })
        } else {
            this.setState({
                refreshing: false,
                modalVisibleRenameFile: false,
                name: null
            })
        }
    }

    /*
     * Callback when file downloaded and update with new datas
     */
    afterDownloadFile(downloaded, path, statusCode) {
        this.statusCodeGesture(statusCode)

        if(downloaded == true && path != null) {
            this.displayToastMessage('File saved : ' + path)
        } else {
            this.displayToastMessage('An error occurred during the download. Try again later!')
        }

        this.setState({
            refreshing: false,
            modalVisibleFolder: false,
        })
    }

    /*
     * Callback when file deleted and update with new datas
     */
    afterDeleteFile(deleted, responseJson, statusCode) {
        this.statusCodeGesture(statusCode)

        if(deleted == true) {
            for (var i = 0; i < this.state.filesData.length; i++) {
                if(this.state.filesData[i].id == this.state.currentId) {
                    this.state.filesData.splice(i, 1);
                }
            }

            this.setState({
                refreshing: false,
                filesData: this.state.filesData,
                modalVisibleFile: false,
            })
        } else {
            this.setState({
                refreshing: false,
                modalVisibleFile: false,
            })
            this.displayToastMessage('An error occurred while deleting.')
        }
    }

    /*
     * Callback when file uploaded and update with new datas
     */
    afterFileUpload(responseJson, statusCode) {
        this.statusCodeGesture(statusCode, responseJson)

        if(responseJson.id && responseJson.name) {
            this.state.filesData.unshift(responseJson)
        }

        this.setState({
            refreshing: false,
            modalVisibleCircleButton: false,
        })
    }

    /*
     * Callback when folder shared and update with new datas
     */
    afterShareFolder(responseJson, statusCode) {
        this.statusCodeGesture(statusCode, responseJson)

        if(responseJson.link) {
            this.setState({
                refreshing: false,
                modalVisibleShare: true,
                shareLink: responseJson.link
            })
        }

        this.setState({
            refreshing: false,
        })
    }

    /*
    Folder methods
    */

    /*
     * Method that get all in the current folder
     */
    getAllFolder() {
        const { navigation } = this.props;
        const navigation_folder_id = navigation.getParam('folder_id', null);
        const navigation_folder_name = navigation.getParam('folder_name', 'Home');
        this.setState({actualFolderId: navigation_folder_id})
        clientInstance.getAllFolder(this.afterGetAll, navigation_folder_id)
    }

    /*
     * Method that open FolderScreen view
     */
    _onPressFolder = (folder_id, folder_name) => {
        this.props.navigation.navigate('FolderScreen', {
              folder_id: folder_id,
              folder_name: folder_name,
        });
    }

    /*
     * Method that set current id of folder and open folder modal
     */
    _onLongPressFolder = (folder_id) => {
        this.setState({
            currentId: folder_id,
            modalVisibleFolder: true
        })
    }

    /*
     * Method that close folder modal and open rename folder modal
     */
    _onPressFolderRename = ()=> {
         this.setState({
             modalVisibleFolder: false,
             modalVisibleRenameFolder: true,
         })
    }

    /*
     * Method that close rename folder modal and set name variable to null
     */
    _onPressCancelRenameFolder = () => {
        this.setState({
            modalVisibleRenameFolder: false,
            name: null
        })
    }

    /*
     * Method that start loading and rename folder
     */
    _onPressValidateRenameFolder = () => {
        this.setLoadingStateToTrue()
        clientInstance.renameFolder(this.afterRenameFolder, this.state.currentId, this.state.name)
    }

    /*
     * Method that start loading and delete folder
     */
    _onPressFolderDelete = () => {
        this.setLoadingStateToTrue()
        clientInstance.deleteFolder(this.afterDeleteFolder, this.state.currentId)
    }

    /*
     * Method that close circle button modal and open create folder modal
     */
    _onPressCreateFolder = () => {
        this.setState({
            modalVisibleCreateFolder: true,
            modalVisibleCircleButton: false
        })
    }

    /*
     * Method that start loading and create folder
     */
    _onPressValidateCreateFolder = () => {
        this.setLoadingStateToTrue()
        clientInstance.createFolder(this.afterCreateFolder, this.state.name, this.state.actualFolderId)
    }

    /*
     * Method that display the create folder modal
     */
    _onPressCancelCreateFolder = () => {
        this.setState({
            modalVisibleCreateFolder: false,
            name: null
        })
    }

    /*
     * Method that upload a file from library's phone
     */
    _onPressUpload = () => {
        DocumentPicker.show({
            filetype: [DocumentPickerUtil.allFiles()],
        },(error,res) => {
            if(res) {
                this.setLoadingStateToTrue()
                clientInstance.uploadFile(this.afterFileUpload, res, this.state.actualFolderId)
            }
        });
    }

    /*
     * Method that share a folder
     */
    _onPressFolderShare = () => {
        this.setState({
            refreshing: true,
            modalVisibleFolder: false
        })
        clientInstance.shareFolder(this.afterShareFolder, this.state.currentId)
    }


    /*
    File methods
    */

    /*
     * Method that open FileScreen view
     */
    _onPressFile = (file_id, file_name) => {
        this.props.navigation.navigate('FileScreen', {
              file_id: file_id,
              file_name: file_name,
        });
    }

    /*
     * Method that set current object and open file modal
     */
    _onLongPressFile = (file_id) => {
        this.setState({
            currentId: file_id,
            modalVisibleFile: true
        })
    }

    /*
     * Method that close file modal and open rename file modal
     */
    _onPressFileRename = () => {
         this.setState({
             modalVisibleFile: false,
             modalVisibleRenameFile: true,
         })
    }

    /*
     * Method that close rename file modal
     */
    _onPressCancelRenameFile = () => {
        this.setState({
            modalVisibleRenameFile: false
        })
    }

    /*
     * Method that start loading and rename file
     */
    _onPressValidateRenameFile = () => {
        this.setLoadingStateToTrue()
        clientInstance.renameFile(this.afterRenameFile, this.state.currentId, this.state.name)
    }

    /*
     * Method that start loading and delete file
     */
    _onPressFileDelete = () => {
        this.setLoadingStateToTrue()
        clientInstance.deleteFile(this.afterDeleteFile, this.state.currentId)
    }

    /*
     * Method that start loading and download file
     */
    _onPressFileDownload = () => {
        this.setLoadingStateToTrue()

        file_name = null
        mime_type = null
        for (var i = 0; i < this.state.filesData.length; i++) {
            if(this.state.filesData[i].id == this.state.currentId) {
                file_name = this.state.filesData[i].name
                file_extension = this.state.filesData[i].extension
                mime_type = this.state.filesData[i].type
            }
        }

        if(file_name == null || file_extension == null || mime_type == null) {
            this.displayToastMessage('Error during the download of the file')
            this.setState({refreshing: false, modalVisibleFile: false})
        } else {
            clientInstance.downloadFile(this.afterDownloadFile, this.state.currentId, file_name, file_extension, mime_type)
        }
    }


    /*
     * Render method
     */
    render() {
        separator = null
        if(this.state.foldersData === null || this.state.filesData === null) {
            renderDatas = <View style={{flex: 1, flexDirection: 'row', alignItems:'center', justifyContent:'center', height: this.state.height, width: this.state.width, paddingBottom: Header.HEIGHT}}><ActivityIndicator size="large"/></View>;
        } else if(this.state.foldersData.length == 0 && this.state.filesData.length == 0) {
            renderDatas = <View style={{flex: 1, flexDirection: 'row', alignItems:'center', justifyContent:'center', height: this.state.height, width: this.state.width, paddingBottom: Header.HEIGHT}}><Text>No data here. Time to begin!</Text></View>;
        } else {
            folders_files_array = this.state.foldersData.concat(this.state.filesData)
            separator = <View style={{height: 74, borderTopWidth: 1, borderColor: '#eeeeee', marginLeft: 45, marginRight: 45}}></View>;
            renderDatas = folders_files_array.map((current, index, array) => {
                return current.is_dir ?
                    <View key={'folder_' + current.id} style={{flex: 1, flexDirection: 'row'}}>
                        <TouchableOpacity style={{padding: 10, flex: 1, flexDirection: 'row'}} onPress={() => this._onPressFolder(current.id, current.name)} onLongPress={() => this._onLongPressFolder(current.id)}>
                            <View style={{width: 50}}>
                                <Image style={{height: 30, width: 30}} source={require("../images/folder_logo.png")} resizeMode="contain"/>
                            </View>
                            <View>
                                <Text style={{fontSize: 18, color: '#333', paddingTop: 5}}>{current.name}</Text>
                            </View>
                        </TouchableOpacity>
                        <TouchableOpacity style={{flex: 0.20}} onPress={() => this._onLongPressFolder(current.id)}>
                            <View style={{alignItems:'center', justifyContent:'center', flex: 1, flexDirection: 'row'}}>
                                    <MaterialCommunityIcons name="dots-vertical" size={25}/>
                            </View>
                        </TouchableOpacity>
                    </View>
                :
                    <View key={'file_' + current.id} style={{flex: 1, flexDirection: 'row'}}>
                        <TouchableOpacity style={{padding: 10, flex: 1, flexDirection: 'row'}} onPress={() => this._onPressFile(current.id, current.name)} onLongPress={() => this._onLongPressFile(current.id)}>
                            <View style={{width: 50}}>
                                <Image style={{height: 30, width: 30}} source={require("../images/picture_logo_1.png")} resizeMode="contain"/>
                            </View>
                            <View>
                                <Text style={{fontSize: 18, color: '#333', paddingTop: 5}}>{current.name}</Text>
                            </View>
                        </TouchableOpacity>
                        <TouchableOpacity style={{flex: 0.20}} onPress={() => this._onLongPressFile(current.id)}>
                            <View style={{alignItems:'center', justifyContent:'center', flex: 1, flexDirection: 'row'}}>
                                    <MaterialCommunityIcons name="dots-vertical" size={25}/>
                            </View>
                        </TouchableOpacity>
                    </View>
            });
        }

        return (
            <View style={{flex: 1}}>
                <ScrollView style={{backgroundColor: '#ffffff'}} refreshControl={<RefreshControl refreshing={this.state.refreshing} onRefresh={this._onRefresh.bind(this)}/>}>
                    <Modal animationType="slide" transparent={true} visible={this.state.modalVisibleFolder} onRequestClose={() => { this.setState({modalVisibleFolder: false}) }}>
                        <View style={styles.view_modal}>
                            <View style={styles.subview_modal}>
                                <TouchableOpacity onPress={this._onPressFolderRename} style={styles.button_modal}>
                                    <Text style={styles.text_button_modal}>Rename</Text>
                                </TouchableOpacity>
                                <TouchableOpacity onPress={this._onPressFolderDelete} style={styles.other_button_modal}>
                                    <Text style={styles.text_button_modal}>Delete</Text>
                                </TouchableOpacity>
                                <TouchableOpacity onPress={this._onPressFolderShare} style={styles.other_button_modal}>
                                    <Text style={styles.text_button_modal}>Share</Text>
                                </TouchableOpacity>
                                <TouchableOpacity onPress={() => { this.setState({modalVisibleFolder: false}) }} style={styles.other_button_modal}>
                                    <Text style={styles.text_button_modal}>Close</Text>
                                </TouchableOpacity>
                            </View>
                        </View>
                    </Modal>

                    <Modal animationType="slide" transparent={true} visible={this.state.modalVisibleFile} onRequestClose={() => { this.setState({modalVisibleFile: false}) }}>
                        <View style={styles.view_modal}>
                            <View style={styles.subview_modal}>
                                <TouchableOpacity onPress={this._onPressFileRename} style={styles.button_modal}>
                                    <Text style={styles.text_button_modal}>Rename</Text>
                                </TouchableOpacity>
                                <TouchableOpacity onPress={this._onPressFileDelete} style={styles.other_button_modal}>
                                    <Text style={styles.text_button_modal}>Delete</Text>
                                </TouchableOpacity>
                                <TouchableOpacity onPress={this._onPressFileDownload} style={styles.other_button_modal}>
                                    <Text style={styles.text_button_modal}>Download</Text>
                                </TouchableOpacity>
                                <TouchableOpacity onPress={() => { this.setState({modalVisibleFile: false}) }} style={styles.other_button_modal}>
                                    <Text style={styles.text_button_modal}>Close</Text>
                                </TouchableOpacity>
                            </View>
                        </View>
                    </Modal>

                    <Modal transparent={true} visible={this.state.modalVisibleRenameFolder} onRequestClose={() => { this.setState({modalVisibleRenameFolder: false}) }}>
                        <View style={styles.view_modal}>
                            <View style={styles.subview_modal}>
                                <TextInput style={{width: '90%', textAlign: 'center'}} placeholder={'New folder name'} onChangeText={(name) => this.setState({name})} value={this.state.name}/>
                                <TouchableOpacity onPress={this._onPressValidateRenameFolder} style={styles.button_modal}>
                                    <Text style={styles.text_button_modal}>Validate</Text>
                                </TouchableOpacity>
                                <TouchableOpacity onPress={this._onPressCancelRenameFolder} style={styles.other_button_modal}>
                                    <Text style={styles.text_button_modal_red}>Cancel</Text>
                                </TouchableOpacity>
                            </View>
                        </View>
                    </Modal>

                    <Modal animationType="slide" transparent={true} visible={this.state.modalVisibleRenameFile} onRequestClose={() => { this.setState({modalVisibleRenameFile: false}) }}>
                        <View style={styles.view_modal}>
                            <View style={styles.subview_modal}>
                                <TextInput style={{width: '90%', textAlign: 'center'}} placeholder={'New file name'} onChangeText={(name) => this.setState({name})} value={this.state.name}/>
                                <TouchableOpacity onPress={this._onPressValidateRenameFile} style={styles.button_modal}>
                                    <Text style={styles.text_button_modal}>Validate</Text>
                                </TouchableOpacity>
                                <TouchableOpacity onPress={this._onPressCancelRenameFile} style={styles.other_button_modal}>
                                    <Text style={styles.text_button_modal_red}>Cancel</Text>
                                </TouchableOpacity>
                            </View>
                        </View>
                    </Modal>

                    <Modal animationType="slide" transparent={true} visible={this.state.modalVisibleCircleButton} onRequestClose={() => { this.setState({modalVisibleCircleButton: false}) }}>
                        <View style={styles.view_modal}>
                            <View style={styles.subview_modal}>
                                <TouchableOpacity onPress={this._onPressCreateFolder} style={styles.button_modal}>
                                    <Text style={styles.text_button_modal}>Create folder</Text>
                                </TouchableOpacity>
                                <TouchableOpacity onPress={this._onPressUpload} style={styles.other_button_modal}>
                                    <Text style={styles.text_button_modal}>Upload</Text>
                                </TouchableOpacity>
                                <TouchableOpacity onPress={() => { this.setState({modalVisibleCircleButton: false}) }} style={styles.other_button_modal}>
                                    <Text style={styles.text_button_modal}>Close</Text>
                                </TouchableOpacity>
                            </View>
                        </View>
                    </Modal>

                    <Modal animationType="slide" transparent={true} visible={this.state.modalVisibleCreateFolder} onRequestClose={() => { this.setState({modalVisibleCreateFolder: false}) }}>
                        <View style={styles.view_modal}>
                            <View style={styles.subview_modal}>
                                <TextInput style={{width: '90%', textAlign: 'center'}} placeholder={'Folder name'} onChangeText={(name) => this.setState({name})} value={this.state.name}/>
                                <TouchableOpacity onPress={this._onPressValidateCreateFolder} style={styles.button_modal}>
                                    <Text style={styles.text_button_modal}>Validate</Text>
                                </TouchableOpacity>
                                <TouchableOpacity onPress={this._onPressCancelCreateFolder} style={styles.other_button_modal}>
                                    <Text style={styles.text_button_modal_red}>Cancel</Text>
                                </TouchableOpacity>
                            </View>
                        </View>
                    </Modal>

                    <Modal animationType="slide" transparent={true} visible={this.state.modalVisibleShare} onRequestClose={() => { this.setState({modalVisibleShare: false, shareLink: null}) }}>
                        <View style={styles.view_modal}>
                            <View style={styles.subview_modal}>
                                <Text selectable={true} onPress={() => { Clipboard.setString(this.state.shareLink); ToastAndroid.showWithGravityAndOffset("Link copied to the clipboard!", ToastAndroid.LONG, ToastAndroid.BOTTOM, 25, 200)}} style={{width: '90%', textAlign: 'center', margin: 10}}>{this.state.shareLink}</Text>
                                <TouchableOpacity onPress={() => { this.setState({modalVisibleShare: false, shareLink: null}) }} style={styles.button_modal}>
                                    <Text style={styles.text_button_modal}>Close</Text>
                                </TouchableOpacity>
                            </View>
                        </View>
                    </Modal>

                    {renderDatas}
                    {separator}
                </ScrollView>

                <ActionButton buttonColor="rgba(231,76,60,1)" onPress={() => this.setState({modalVisibleCircleButton: true})}/>
            </View>
        );
    }
}

const styles = StyleSheet.create({
    button_modal: {
        borderTopWidth: 1,
        borderBottomWidth: 1,
        borderColor: '#eeeeee',
        width: '100%',
        alignItems: 'center',
        paddingHorizontal: 20,
        paddingVertical: 7,
    },
    other_button_modal: {
        borderBottomWidth: 1,
        borderColor: '#eeeeee',
        width: '100%',
        alignItems: 'center',
        paddingHorizontal: 20,
        paddingVertical: 7,
    },
    text_button_modal: {
        fontSize: 17,
        marginTop: 3,
        marginBottom: 3,
    },
    view_modal: {
        flex: 1,
        flexDirection: 'column',
        justifyContent: 'center',
        alignItems: 'center'
    },
    subview_modal: {
        backgroundColor: '#ffffff',
        borderColor: 'black',
        alignItems: 'center',
        borderRadius: 3,
        width: '80%',
        borderWidth: 5,
        borderColor: '#eeeeee'
    },
    text_button_modal_red: {
        fontSize: 17,
        marginTop: 3,
        marginBottom: 3,
        color: 'rgba(231,76,60,1)',
    },
});
