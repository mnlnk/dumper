'use_strict'

let mnlnkRoots = [];

let mnlnkDumpInit = window.mnlnkDumpInit || function (rId) {
    let _blocks = [];
    let _root = document.getElementById('id-' + rId);

    mnlnkRoots.push(_root);

    let _toggle = (e, s) => {
        e.querySelectorAll(s).forEach(e1 => {
            e1.addEventListener('click', event => {
                let el = event.target;
                let parent = el.parentElement;
                const id = el.classList[0].slice(3);

                parent.classList.toggle('open');
                if (parent.classList.contains('open')) {
                    el.innerText = '<<';
                    el.title = 'Свернуть';
                } else {
                    el.innerText = '>>';
                    el.title = 'Развернуть';
                }

                if (parent.classList.contains('string')) return;
                if (_blocks.includes(id)) return;

                _toggle(parent, ':scope > .content > .row > .block > .toggle');
                _braces(parent, ':scope > .br-' + id);
                _hash(parent, ':scope > .content > .row > .block > .hash');
                _namespace(parent, ':scope > .content > .row > .block > .namespace[data-ns]')
                _recursion(parent, ':scope > .content > .row > .block > .recursion');

                _blocks.push(id);
            });
        });
    };

    let _braces = (e, s) => {
        e.querySelectorAll(s).forEach(e2 => {
            const bId = e2.classList[0].slice(3);

            e2.addEventListener('mouseenter', event => {
                event.target.parentElement.querySelectorAll(':scope .br-' + bId).forEach(e3 => {
                    e3.classList.add('highlight');
                });
            });

            e2.addEventListener('mouseleave', event => {
                event.target.parentElement.querySelectorAll(':scope .br-' + bId).forEach(e4 => {
                    e4.classList.remove('highlight');
                });
            });
        });
    };

    let _hash = (e, s) => {
        e.querySelectorAll(s).forEach(e2 => {
            const hId = e2.classList[0].slice(3);

            e2.addEventListener('mouseenter', event => {
                mnlnkRoots.forEach(mRoot => {
                    mRoot.querySelectorAll(':scope .ha-' + hId).forEach(e3 => {
                        e3.classList.add('highlight');
                    })
                });
            });

            e2.addEventListener('mouseleave', event => {
                mnlnkRoots.forEach(mRoot => {
                    mRoot.querySelectorAll(':scope .ha-' + hId).forEach(e4 => {
                        e4.classList.remove('highlight');
                    })
                });
            });
        });
    };

    let _namespace = (e, s) => {
        e.querySelectorAll(s).forEach(e1 => {
            e1.addEventListener('click', event => {
                let el = event.target;
                let iText = el.innerText;

                el.innerText = el.dataset.ns;
                el.dataset.ns = iText;
            });
        });
    };

    let _recursion = (e, s) => {
        e.querySelectorAll(s).forEach(e2 => {
            const rId = e2.classList[0].slice(3);

            e2.addEventListener('mouseenter', event => {
                _root.querySelectorAll(':scope .br-' + rId).forEach(e3 => {
                    e3.classList.toggle('highlight');
                });
            });

            e2.addEventListener('mouseleave', event => {
                _root.querySelectorAll(':scope .br-' + rId).forEach(e4 => {
                    e4.classList.remove('highlight');
                });
            });
        });
    };

    /**/

    _toggle(_root, ':scope > .row > .block > .toggle');
    _hash(_root, ':scope > .row > .block > .hash');
    _namespace(_root, ':scope > .row > .block > .namespace[data-ns]')

    /**/
}
