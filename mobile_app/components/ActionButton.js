import React, { Component, PropTypes } from 'react';
import {
    StyleSheet,
    Text,
    View,
    Animated,
    TouchableOpacity,
    TouchableWithoutFeedback,
} from 'react-native';

const alignMap = {
    center: {
        alignItems: 'center',
        justifyContent: 'flex-end',
        startDegree: 180,
        endDegree: 360,
    },

    left: {
        alignItems: 'flex-start',
        justifyContent: 'flex-end',
        startDegree: 270,
        endDegree: 360,
    },

    right: {
        alignItems: 'flex-end',
        justifyContent: 'flex-end',
        startDegree: 180,
        endDegree: 270,
    },
};

export default class ActionButton extends Component {
    constructor(props) {
        super(props);
        this.state = {
            active: props.active,
            anim: new Animated.Value(props.active ? 1 : 0),
        };

        this.timeout = null;
    }

    componentWillUnmount() {
        clearTimeout(this.timeout);
    }

    getActionButtonStyle() {
        return [styles.actionBarItem, this.getButtonSize()];
    }

    getActionContainerStyle() {
        const {alignItems, justifyContent} = alignMap[this.props.position];
        return [styles.overlay, styles.actionContainer, {
            alignItems,
            justifyContent,
        }];
    }
    getActionsStyle() {
        return [this.getButtonSize()];
    }

    getButtonSize() {
        return {
            width: this.props.size,
            height: this.props.size,
        };
    }


    animateButton() {
        if (this.state.active) {
            this.reset();
            return;
        }

        Animated.spring(this.state.anim, {
            toValue: 1,
            duration: 250,
        }).start();

        this.setState({ active: true });
    }

    reset() {
        Animated.spring(this.state.anim, {
            toValue: 0,
            duration: 250,
        }).start();

        setTimeout(() => {
            this.setState({ active: false });
        }, 250);
    }

    renderButton() {
        return (
            <View style={this.getActionButtonStyle()}>
            <TouchableOpacity activeOpacity={0.85} onLongPress={this.props.onLongPress} onPress={() => {
                this.props.onPress();
                if (this.props.children) {
                    this.animateButton();
                }
            }}>
            <Animated.View
            style={[styles.btn, {
                width: this.props.size,
                height: this.props.size,
                borderRadius: this.props.size / 2,
                backgroundColor: this.state.anim.interpolate({
                    inputRange: [0, 1],
                    outputRange: [this.props.buttonColor, this.props.btnOutRange]
                }),
                transform: [{
                    scale: this.state.anim.interpolate({
                        inputRange: [0, 1],
                        outputRange: [1, this.props.outRangeScale]
                    }),
                },
                {
                    rotate: this.state.anim.interpolate({
                        inputRange: [0, 1],
                        outputRange: ['0deg', this.props.degrees + 'deg']
                    }),
                }],
            }]}>
            {this.renderButtonIcon()}
            </Animated.View>
            </TouchableOpacity>
            </View>
        );
    }

    renderButtonIcon() {
        if (this.props.icon) {
            return this.props.icon;
        }

        return (
            <Animated.Text style={[styles.btnText, {
                color: this.state.anim.interpolate({
                    inputRange: [0, 1],
                    outputRange: [this.props.buttonTextColor, this.props.btnOutRangeTxt]
                })
            }]}>
            +
            </Animated.Text>
        );
    }

    render() {
        let backdrop;
        if (this.state.active) {
            backdrop = (
                <TouchableWithoutFeedback style={styles.overlay} onPress={() => {this.reset(); this.props.onOverlayPress()}}>
                    <Animated.View style={{backgroundColor: this.props.bgColor, opacity: this.state.anim, flex: 1}}>
                        {this.props.backdrop}
                    </Animated.View>
                </TouchableWithoutFeedback>
            );
        }

        return (
            <View pointerEvents="box-none" style={styles.overlay}>
                {backdrop}
                <View pointerEvents="box-none" style={this.getActionContainerStyle()}>
                    {this.renderButton()}
                </View>
            </View>
        );
    }
}

ActionButton.defaultProps = {
    active: false,
    bgColor: 'transparent',
    buttonColor: 'rgba(0,0,0,1)',
    buttonTextColor: 'rgba(255,255,255,1)',
    position: 'right',
    outRangeScale: 1,
    autoInactive: true,
    onPress: () => {},
    onOverlayPress: () => {},
    backdrop: false,
    degrees: 135,
    size: 55,
    itemSize: 36,
    radius: 100,
    btnOutRange: 'rgba(0,0,0,1)',
    btnOutRangeTxt: 'rgba(255,255,255,1)',
};

const styles = StyleSheet.create({
    overlay: {
        position: 'absolute',
        bottom: 0,
        left: 0,
        right: 0,
        top: 0,
        backgroundColor: 'transparent',
    },
    actionContainer: {
        flexDirection: 'column',
        padding: 10,
    },
    actionBarItem: {
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor: 'transparent',
    },
    btn: {
        justifyContent: 'center',
        alignItems: 'center',
        shadowOpacity: 0.3,
        shadowOffset: {
            width: 0,
            height: 1,
        },
        shadowColor: '#444',
        shadowRadius: 1,
    },
    btnText: {
        marginTop: -4,
        fontSize: 24,
        backgroundColor: 'transparent',
        position: 'relative',
    },
});
