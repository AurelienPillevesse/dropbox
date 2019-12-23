import React, { Component, PropTypes } from 'react';
import {
    StyleSheet,
    Text,
    View,
    TouchableOpacity,
    TextInput,
    ScrollView,
    ActivityIndicator,
    AsyncStorage,
    Dimensions,
    Modal
} from 'react-native';
import { NavigationActions, Header } from 'react-navigation';
import clientInstance from './Client';

export default class SignInScreen extends Component {

    /*
     * Constructor
     */
    constructor(props) {
        super(props);

        var {height, width} = Dimensions.get('window');
        this.state = {
            username: null,
            password: null,
            isLoading: false,
            messageError: null,
            height: height - (Header.HEIGHT),
            width: width,
        };
    }

    /*
     * Method that set loading to true, send login request to the api
     * and redirect or not according to the status code
     */
    _onPressSignIn = () => {
        this.setState({isLoading: true})

        fetch(clientInstance.getUrl() + '/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
	            username: this.state.username,
	            password: this.state.password,
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

            this.setState({isLoading: false})
            this.statusCodeGesture(res.statusCode, res.data)

            if(res.statusCode == 200) {
                AsyncStorage.setItem('supfiles_user_token', res.data.token)
                .then(() => {
                    const resetAction = NavigationActions.reset({
                        index: 0,
                        actions: [
                            NavigationActions.navigate({ routeName: 'HomeScreen'})
                        ]
                    })
                    this.props.navigation.dispatch(resetAction)
                })
            }
        })
        .catch((error) =>{
            console.error(error);
        });
    }

    /*
     * Method that set message attribute
     */
    statusCodeGesture(statusCode, message) {
        if(statusCode == 200) {
            this.setState({messageError: null})
        }

        if(statusCode == 400) {
            this.setState({messageError: message.errorMessage})
        }
    }

    /*
     * Render method
     */
    render() {
        errorMessage = loading = null
        if(this.state.isLoading){
            loading = <Modal transparent={true} visible={this.state.isLoading} onRequestClose={() => {}}>
                <View style={{backgroundColor: 'transparent', flex: 1, flexDirection: 'row', alignItems:'center', justifyContent:'center', height: this.state.height, width: this.state.width, paddingBottom: Header.HEIGHT}}>
                    <ActivityIndicator size="large"/>
                </View>
            </Modal>;
        }

        if(this.state.messageError) {
            errorMessage = <View style={{justifyContent: 'center', alignItems: 'center'}}>
                <Text style={{color: 'red'}}>{this.state.messageError}</Text>
            </View>;
        }

        return (
            <ScrollView style={styles.container}>
                {loading}
                <View style={styles.button_container}>
                    <TextInput style={styles.text_input} placeholder={'Email/Username'} onChangeText={(username) => this.setState({username})} value={this.state.username}/>
                    <TextInput style={styles.text_input} secureTextEntry={true} placeholder={'Password'} onChangeText={(password) => this.setState({password})} value={this.state.password}/>

                    <TouchableOpacity style={styles.button} onPress={this._onPressSignIn}>
                        <Text style={styles.text}>Sign in</Text>
                    </TouchableOpacity>

                    {errorMessage}
                </View>
            </ScrollView>
        );
    }
}

const styles = StyleSheet.create({
    container: {
        backgroundColor: 'white'
    },
    text : {
        textAlign: 'center',
    },
    text_input : {
        marginLeft: 15,
        marginRight: 15,
    },
    button_container: {
        flex: 1,
        justifyContent: 'center',
    },
    button: {
        margin: 5,
        marginLeft: 15,
        marginRight: 15,
        backgroundColor: '#ffffff',
        borderWidth: 1,
        borderColor: '#eeeeee',
        paddingHorizontal: 20,
        paddingVertical: 15,
    },
});
