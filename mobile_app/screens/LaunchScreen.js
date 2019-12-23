import React, { Component, PropTypes } from 'react';
import {
  StyleSheet,
  Text,
  View,
  ScrollView,
  TouchableOpacity,
  Image,
  AsyncStorage,
  Dimensions,
} from 'react-native';

export default class LaunchScreen extends Component {

    /*
     * Constructor
     */
    constructor(props) {
        super(props);

        var {height, width} = Dimensions.get('window');
        this.state = {
            height: height,
            width: width
        }
    }

    /*
     * Method that redirect to SignInScreen view
     */
    _onPressSignIn = () => {
        this.props.navigation.navigate("SignInScreen")
    }

    /*
     * Method that redirect to RegisterScreen view
     */
    _onPressRegister = () => {
        this.props.navigation.navigate("RegisterScreen")
    }

    /*
     * Render method
     */
    render() {
        return (
            <ScrollView style={{backgroundColor: 'white'}}>
                <View>
                    <Image style={{width: this.state.width, height: this.state.height/1.5}} resizeMode={"center"} source={require('./../images/logo.png')}/>
                </View>
                <TouchableOpacity style={styles.button} onPress={this._onPressSignIn}>
                    <Text style={styles.text}>Sign in</Text>
                </TouchableOpacity>
                <TouchableOpacity style={styles.button} onPress={this._onPressRegister}>
                    <Text style={styles.text}>Register</Text>
                </TouchableOpacity>
            </ScrollView>
        );
    }
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        justifyContent: 'center',
    },
    text : {
        textAlign: 'center',
    },
    title : {
        textAlign: 'center',
        fontSize: 22,
    },
    button_container: {
        flex: 1,
        justifyContent: 'center',
    },
    image_container: {
        justifyContent: 'center',
        flexDirection: 'row',
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

/* button & button_container :
flexDirection: 'row',
justifyContent: 'space-between',
*/
