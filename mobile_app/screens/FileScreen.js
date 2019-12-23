import React, { Component, PropTypes } from 'react';
import {
  StyleSheet,
  Text,
  View,
  TouchableOpacity,
  TextInput,
  ActivityIndicator,
  AsyncStorage,
  ScrollView,
  Dimensions,
  ToastAndroid,
  WebView,
} from 'react-native';
import FastImage from 'react-native-fast-image';
import Video from 'react-native-video';
import clientInstance from './Client';
import { NavigationActions, Header } from 'react-navigation';

export default class FileScreen extends Component {

    /*
     * Constructor
     */
    constructor(props) {
        super(props);

        var {height, width} = Dimensions.get('window');
        const { navigation } = this.props;
        this.state = {
            file_id: navigation.getParam('file_id', null),
            file : null,
            type: null,
            shorterType : null,
            height: height - (Header.HEIGHT*2),
            heightStandard: height - Header.HEIGHT,
            width: width,
            loading: false,
        };

        this.afterGetFile = this.afterGetFile.bind(this);
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
    statusCodeGesture(statusCode) {
        if(statusCode == 404) {
            this.props.navigation.goBack()
            this.displayToastMessage("This resource doesn't exists!")
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
     * Callback when get file
     */
    afterGetFile(responseJson, statusCode) {
        this.statusCodeGesture(statusCode)

        if(responseJson.file) {
            shorterType = responseJson.file.type.split('/')[0]

            this.setState({
                file: responseJson.file.file_path,
                type: responseJson.file.type,
                shorterType: shorterType,
                loading: false,
            })
        }

        this.setState({
            loading: false,
        })
    }

    /*
     * Get file when component is mount and set loading to true
     */
    componentDidMount() {
        this.setState({
            loading: true,
        })
        clientInstance.getFile(this.afterGetFile, this.state.file_id)
    }

    /*
     * Render method
     */
    render() {
        if(this.state.loading) {
            return (
                <View style={{flex: 1, flexDirection: 'row', alignItems:'center', justifyContent:'center', height:  this.state.heightStandard, width: this.state.width, paddingBottom: Header.HEIGHT}}>
                    <ActivityIndicator size="large"/>
                </View>
            )
        }

        if(this.state.shorterType == 'image') {
            return (
                <View style={styles.fullScreen}>
                    <FastImage
                        style={{height: this.state.height, width: this.state.width}}
                        source={{
                          uri: clientInstance.getDomain() + '/uploads/files/' + this.state.file,
                          priority: FastImage.priority.high,
                        }}
                        resizeMode={FastImage.resizeMode.contain}
                    />
                </View>
            );
        } else if(this.state.shorterType == 'video') {
            return (
                <View style={styles.container}>
                    <Video source={{uri: clientInstance.getDomain() + '/uploads/files/' + this.state.file}}
                       paused={false}
                       repeat={true}
                       resizeMode="contain"
                       style={{flex: 1, flexDirection: 'column', alignItems:'center', justifyContent:'center', height:  this.state.heightStandard, width: this.state.width, paddingBottom: Header.HEIGHT}} />
                </View>
            )
        } else if(this.state.type == 'text/plain') {
            return (
                <View style={styles.container}>
                    <WebView
                        source={{uri: clientInstance.getDomain() + '/uploads/files/' + this.state.file}}
                        style={{flex: 1, flexDirection: 'column', alignItems:'center', justifyContent:'center', height:  this.state.heightStandard, width: this.state.width, paddingBottom: Header.HEIGHT}} />
                </View>
            )
        } else {
            return (
                <View style={{flex: 1, flexDirection: 'column', alignItems:'center', justifyContent:'center', height:  this.state.heightStandard, width: this.state.width, paddingBottom: Header.HEIGHT}}>
                    <Text>This type of content is not available for the moment.</Text>
                    <Text>But you can download it!</Text>
                </View>
            )
        }
    }
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
    },
    text : {
        textAlign: 'center',
    },
    text_input : {
        margin: 5,
    },
    button_container: {
        flex: 1,
        justifyContent: 'center',
    },
    fullScreen: {
        position: 'absolute',
        top: 0,
        left: 0,
        bottom: 0,
        right: 0,
    },
});
