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
import { NavigationActions } from 'react-navigation';

export default class SplashScreen extends Component {

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

        setTimeout(function(){
            AsyncStorage.getItem('supfiles_user_token')
            .then((token) => {
                if (token !== null){
                    this.redirect('HomeScreen')
                } else {
                    this.redirect('LaunchScreen')
                }
            })
        }.bind(this), 1500);
    }

    /*
     * Method that redirect the a view
     */
    redirect(route) {
        const resetAction = NavigationActions.reset({
          index: 0,
          actions: [
            NavigationActions.navigate({ routeName: route})
          ]
        })
        this.props.navigation.dispatch(resetAction)
    }

    /*
     * Render method
     */
    render() {
        return (
            <View style={{backgroundColor: 'white', flex: 1, flexDirection: 'row', alignItems:'center', justifyContent:'center', height: this.state.height, width: this.state.width}}>
                <Image style={{width: this.state.width, height: this.state.height/1.5}} resizeMode={"center"} source={require('./../images/logo.png')}/>
            </View>
        );
    }
}
