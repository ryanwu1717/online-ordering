import React, { Component } from 'react';
import { EditorState, Modifier, ContentState, } from 'draft-js';
import { Editor } from 'react-draft-wysiwyg';
import 'react-draft-wysiwyg/dist/react-draft-wysiwyg.css';
import styled from 'styled-components';

// Following sample is based the article https://dev.to/rose/draft-js-simple-content-manipulation-b7a

class EditorConvertToHTML extends Component {
    constructor(props) {
        super(props);
        this.state = {
            editorState: EditorState.createWithContent(
                ContentState.createFromText(this.props.record_content)
            ),
        };
        this.sendTextToEditor = this.sendTextToEditor.bind(this)
        this.setTextToEditor = this.setTextToEditor.bind(this)
        this.getContent = this.getContent.bind(this)
        this.changeText=this.changeText.bind(this)
    }

    componentDidMount() {
        this.focusEditor();
        this.changeText(this.props.record_content)

    }
    componentDidUpdate(prevProps, prevState, snapshot) {
        if (this.props.meet_id !== prevProps.meet_id) {
            this.setState({
                editorState: EditorState.createWithContent(
                    ContentState.createFromText(this.props.record_content)
                ),
            })

        }
    }

    setEditor = (editor) => {
        this.editor = editor;
    };

    focusEditor = () => {
        if (this.editor) {
            this.editor.focusEditor();
        }
    };

    onEditorStateChange = (editorState) => {
        // console.log(editorState)s
        this.setState({
            editorState,
        });
        this.props.changeMeetRecord(this.state.editorState.getCurrentContent().getPlainText())
    };
    getContent() {
        console.log(this.state.editorState.getCurrentContent().getPlainText())
    }
    sendTextToEditor = (event) => {
        let create_date = event.target.attributes.create_date.value
        let content = event.target.attributes.content.value
        let tracking_name = event.target.attributes.tracking_name.value
        let text = `建立日期 : ${create_date}\n標題 : ${content}\n說明 : ${tracking_name}\n`
        this.setState({
            editorState: this.insertText(text, this.state.editorState)
        });
        this.focusEditor();
    }
    setTextToEditor = (text) => {

        this.setState({
            editorState: this.insertText(text, this.state.editorState)
        });
        this.focusEditor();
    }
    changeText = (text) => {
        this.setState({
            editorState: EditorState.createWithContent(
                ContentState.createFromText(text)
            ),
        })
    }
    insertText = (text, editorState) => {
        const currentContent = editorState.getCurrentContent(),
            currentSelection = editorState.getSelection();

        const newContent = Modifier.replaceText(
            currentContent,
            currentSelection,
            text
        );

        const newEditorState = EditorState.push(editorState, newContent, 'insert-characters');
        return EditorState.forceSelection(newEditorState, newContent.getSelectionAfter());
    }

    render() {
        const { editorState } = this.state;
        return (
            <>

                <Editor
                    ref={this.setEditor}
                    editorState={editorState}
                    wrapperClassName="demo-wrapper"
                    editorClassName="demo-editor"
                    onEditorStateChange={this.onEditorStateChange}
                />
            </>
        );
    }
}

export default EditorConvertToHTML;



