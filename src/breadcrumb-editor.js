/**
 * AWT — per-post breadcrumb control + live preview.
 *
 * Adds a "Breadcrumb" panel to the block editor's Document (Page/Post) settings
 * sidebar with:
 *   - a "Hide breadcrumb on this page" toggle bound to the `awt_hide_breadcrumb`
 *     post meta (registered server-side with show_in_rest), and
 *   - a live preview of the trail the auto-emit will render for this post.
 *
 * Why a sidebar preview (not the canvas): the auto-emitted breadcrumb is a
 * server-side `render_block` injection into the template's <main>, which never
 * runs in the editor canvas. Reproducing it in the canvas would mean reaching
 * into Gutenberg's iframe DOM (fragile, gets wiped on re-render). The panel
 * preview shows the exact trail + whether it'll appear, right next to the toggle.
 *
 * The preview is only meaningful for singular views (the editor always edits one
 * post/page), so the trail is: Home › [ancestors] › this post. Matches the
 * server's singular branch in inc/breadcrumb-auto-emit.php → build_trail().
 *
 * Hand-written (no JSX/build) using the wp.* globals so it can ship from the
 * theme, which has no JavaScript build pipeline.
 * @param {Object} wp The global wp object.
 */
(function (wp) {
	if (!wp || !wp.plugins || !wp.element || !wp.data) {
		return;
	}

	const el = wp.element.createElement;
	const __ = wp.i18n.__;
	const META_KEY = 'awt_hide_breadcrumb';
	const CFG = window.awtBreadcrumbPreview || {
		homeText: 'Home',
		globalEnabled: true,
		blogTitle: '',
	};

	const PluginDocumentSettingPanel =
		(wp.editor && wp.editor.PluginDocumentSettingPanel) ||
		(wp.editPost && wp.editPost.PluginDocumentSettingPanel);

	if (
		!PluginDocumentSettingPanel ||
		!wp.components ||
		!wp.components.ToggleControl
	) {
		return;
	}

	// Build the array of crumb labels (last item is the current page).
	function useTrail() {
		return wp.data.useSelect(function (select) {
			const ed = select('core/editor');
			if (!ed) {
				return [];
			}
			const core = select('core');
			const postType = ed.getCurrentPostType();
			const ptObj = core ? core.getPostType(postType) : null;
			const hierarchical = ptObj ? !!ptObj.hierarchical : false;

			let crumbs = [CFG.homeText || __('Home', 'awt')];

			if (hierarchical) {
				// Walk the parent chain by title (oldest ancestor first).
				const ancestors = [];
				let pid =
					parseInt(ed.getEditedPostAttribute('parent'), 10) || 0;
				let guard = 0;
				while (pid && guard < 10) {
					const rec = core.getEntityRecord('postType', postType, pid);
					if (!rec) {
						ancestors.unshift('…'); // still resolving
						break;
					}
					ancestors.unshift(
						rec.title && rec.title.rendered
							? rec.title.rendered
							: '…'
					);
					pid = parseInt(rec.parent, 10) || 0;
					guard++;
				}
				crumbs = crumbs.concat(ancestors);
			} else if (postType === 'post' && CFG.blogTitle) {
				crumbs.push(CFG.blogTitle);
			}

			const title = (ed.getEditedPostAttribute('title') || '').trim();
			crumbs.push(title || __('(no title yet)', 'awt'));
			return crumbs;
		}, []);
	}

	function Preview(props) {
		const crumbs = props.crumbs;
		// Carbon breadcrumb markup so the editor's theme.css styles it.
		const items = crumbs.map(function (label, i) {
			const isLast = i === crumbs.length - 1;
			const inner = isLast
				? el('span', { 'aria-current': 'page' }, label)
				: el(
						'a',
						{
							className: 'cds--link',
							href: '#',
							onClick(e) {
								e.preventDefault();
							},
						},
						label
					);
			return el(
				'li',
				{ className: 'cds--breadcrumb-item', key: i },
				inner
			);
		});
		return el(
			'nav',
			{
				className: 'cds--breadcrumb',
				'aria-label': __('Breadcrumb preview', 'awt'),
				style: { marginBlockStart: '0.75em' },
			},
			el('ol', { className: 'cds--breadcrumb__list' }, items)
		);
	}

	function Panel() {
		const meta = wp.data.useSelect(function (select) {
			return select('core/editor').getEditedPostAttribute('meta') || {};
		}, []);
		const editPost = wp.data.useDispatch('core/editor').editPost;
		const hidden = !!(meta && meta[META_KEY]);
		const crumbs = useTrail();

		const children = [
			el(wp.components.ToggleControl, {
				key: 'toggle',
				__nextHasNoMarginBottom: true,
				label: __('Hide breadcrumb on this page', 'awt'),
				checked: hidden,
				onChange(value) {
					const next = {};
					if (meta) {
						for (const k in meta) {
							if (Object.prototype.hasOwnProperty.call(meta, k)) {
								next[k] = meta[k];
							}
						}
					}
					next[META_KEY] = value;
					editPost({ meta: next });
				},
			}),
		];

		// Preview / status area.
		const willShow = CFG.globalEnabled && !hidden;
		let note;
		if (!CFG.globalEnabled) {
			note = __(
				'Breadcrumbs are turned off site-wide (AWT Settings → Navigation), so none will appear here.',
				'awt'
			);
		} else if (hidden) {
			note = __(
				'Hidden on this page. With the toggle off it would render:',
				'awt'
			);
		} else {
			note = __(
				'Preview of the breadcrumb that appears at the top of this page:',
				'awt'
			);
		}

		children.push(
			el(
				'p',
				{
					key: 'note',
					style: {
						marginBlockStart: '1em',
						marginBlockEnd: '0',
						color: '#646970',
						fontSize: '12px',
					},
				},
				note
			)
		);

		// Always render the trail preview (dimmed when it won't actually show)
		// so the author can see what it would contain either way.
		children.push(
			el(
				'div',
				{
					key: 'preview',
					style: { opacity: willShow ? 1 : 0.45 },
				},
				el(Preview, { crumbs })
			)
		);

		return el(
			PluginDocumentSettingPanel,
			{ name: 'awt-breadcrumb', title: __('Breadcrumb', 'awt') },
			children
		);
	}

	wp.plugins.registerPlugin('awt-breadcrumb-controls', { render: Panel });
})(window.wp);
