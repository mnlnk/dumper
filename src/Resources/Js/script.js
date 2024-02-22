'use_strict'

let mnlnkRoots = [];

let mnlnkDumpInit = window.mnlnkDumpInit || function (rootId) {
    let _blocks = [];
    let _root = document.getElementById('md_id-' + rootId);

    mnlnkRoots.push(_root);

    let _toggle = (elem, sel) => {
        elem.querySelectorAll(sel).forEach(el => {
            el.addEventListener('click', event => {
                let target = event.target;
                let parent = target.parentElement;
                let id = target.classList[0].slice(6);

                parent.classList.toggle('md_open');
                if (parent.classList.contains('md_open')) {
                    target.innerText = '<<';
                    target.title = 'Collapse';
                }
                else {
                    target.innerText = '>>';
                    target.title = 'Expand';
                }

                if (parent.classList.contains('md_string'))
                    return;

                if (_blocks.includes(id))
                    return;

                _toggle(parent, ':scope > .md_content > .md_row > .md_block > .md_toggle');
                _braces(parent, ':scope > .md_br-' + id);
                _hash(parent, ':scope > .md_content > .md_row > .md_block > .md_hash');
                _namespace(parent, ':scope > .md_content > .md_row > .md_block > .md_namespace[data-ns]')
                _recursion(parent, ':scope > .md_content > .md_row > .md_block > .md_recursion');

                _blocks.push(id);
            });
        });
    };

    let _braces = (elem, sel) => {
        elem.querySelectorAll(sel).forEach(el => {
            let bracesId = el.classList[0].slice(6);

            el.addEventListener('mouseenter', event => {
                event.target.parentElement.querySelectorAll(':scope .md_br-' + bracesId).forEach(e => {
                    e.classList.add('md_highlight');
                });
            });

            el.addEventListener('mouseleave', event => {
                event.target.parentElement.querySelectorAll(':scope .md_br-' + bracesId).forEach(e => {
                    e.classList.remove('md_highlight');
                });
            });
        });
    };

    let _hash = (elem, sel) => {
        elem.querySelectorAll(sel).forEach(el => {
            let hashId = el.classList[0].slice(6);

            el.addEventListener('mouseenter', event => {
                mnlnkRoots.forEach(mRoot => {
                    mRoot.querySelectorAll(':scope .md_ha-' + hashId).forEach(e => {
                        e.classList.add('md_highlight');
                    })
                });
            });

            el.addEventListener('mouseleave', event => {
                mnlnkRoots.forEach(mRoot => {
                    mRoot.querySelectorAll(':scope .md_ha-' + hashId).forEach(e => {
                        e.classList.remove('md_highlight');
                    })
                });
            });
        });
    };

    let _namespace = (elem, sel) => {
        elem.querySelectorAll(sel).forEach(el => {
            el.addEventListener('click', event => {
                let target = event.target;
                let text = target.innerText;

                target.innerText = target.dataset.ns;
                target.dataset.ns = text;
            });
        });
    };

    let _recursion = (elem, sel) => {
        elem.querySelectorAll(sel).forEach(el => {
            let recursionId = el.classList[0].slice(6);

            el.addEventListener('mouseenter', event => {
                _root.querySelectorAll(':scope .md_br-' + recursionId).forEach(e => {
                    e.classList.toggle('md_highlight');
                });
            });

            el.addEventListener('mouseleave', event => {
                _root.querySelectorAll(':scope .md_br-' + recursionId).forEach(e => {
                    e.classList.remove('md_highlight');
                });
            });
        });
    };

    /**/

    _toggle(_root, ':scope > .md_row > .md_block > .md_toggle');
    _hash(_root, ':scope > .md_row > .md_block > .md_hash');
    _namespace(_root, ':scope > .md_row > .md_block > .md_namespace[data-ns]')

    /**/
}
