import React from 'react';
import { StackNavigator } from 'react-navigation';

import LaunchScreen from './screens/LaunchScreen';
import SignInScreen from './screens/SignInScreen';
import RegisterScreen from './screens/RegisterScreen';
import FolderScreen from './screens/FolderScreen';
import FileScreen from './screens/FileScreen';
import SplashScreen from './screens/SplashScreen';

const AppNavigation = new StackNavigator(
    {
        SplashScreen: {
            screen: SplashScreen,
            navigationOptions: {
                header: null,
            },
        },
        LaunchScreen: {
            screen: LaunchScreen,
            navigationOptions: {
                header: null,
            },
        },
        SignInScreen: {
            screen: SignInScreen,
            navigationOptions: {
                title: 'Sign in',
            },
        },
        RegisterScreen: {
            screen: RegisterScreen,
            navigationOptions: {
                title: 'Register',
            },
        },
        HomeScreen: {
            screen: FolderScreen,
            navigationOptions: {
                title: 'Home',
            },
        },
        FolderScreen: {
            screen: FolderScreen,
            navigationOptions: ({ navigation }) => ({
                title: `${navigation.state.params.folder_name}`,
            }),
        },
        FileScreen: {
            screen: FileScreen,
            navigationOptions: ({ navigation }) => ({
                title: `${navigation.state.params.file_name}`,
            }),
        },
    },
);

export default AppNavigation;

/*
{
headerMode: "none"
}
*/
