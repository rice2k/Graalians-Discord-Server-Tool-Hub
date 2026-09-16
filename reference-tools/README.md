# Working Reference Tools

These sources were supplied as known-working Graal tool implementations. They are preserved separately from the themed PHP Tool Hub so the live browser tools can be improved against proven behavior without replacing the current Hub pages blindly.

## Working references

| Reference | Purpose | Notes |
| --- | --- | --- |
| [`packages/gani-edit-working-source.zip`](packages/gani-edit-working-source.zip) | Browser-based GANI editor | Clean source package from the supplied `gani-edit-master.zip`. Contains the HTML/CSS/JavaScript editor and Makefile. The supplied README credits `wijnen@debian.org`. Public upstream/fork history also exists at `wijnen/gani-edit` and `rice2k/gani-edit`. |
| [`packages/graal-gmap-generator-working-source.zip`](packages/graal-gmap-generator-working-source.zip) | C# GMAP generator | Clean source package from the supplied `graal-gmap-generator-master.zip`, including solution/project files, generator code, template level, tests, and GitHub Actions workflow. A separate `rice2k/graal-gmap-generator` repository also exists. |
| [`GraalViewer2-source/`](GraalViewer2-source/) | C# Graal animation/GANI viewer reference source | Browseable source imported from the supplied `GraalViewer2_src.zip`. Generated `bin/` and `obj/` output was not carried over. Core parser/rendering, viewer/window, entry point, texture loader, solution, and original project definition are preserved here. The old project targets .NET Framework 4 Client/x86 and contains absolute SFML.NET reference paths that should be made portable before a modern rebuild. |

## Supplied compiled viewer

The uploaded `GraalAnimViewer2.zip` is a working compiled/runtime package containing the viewer executable, CSFML/SFML.NET DLLs, and sample Graal assets. It is intentionally not copied into this public source-reference folder because it contains compiled third-party/runtime binaries. The source references above are the material intended for code comparison and future Hub improvements.

## How these references should be used

- Compare GANI parsing, sprite-resource handling, frame timing, single-direction behavior, loop/freeze/setback behavior, and previews against `gani-edit` and `GraalViewer2` before changing `gani-editor.php`.
- Compare GMAP layout, level naming, generated level contents, and test expectations against the C# GMAP generator before changing `generatelevels.php`.
- Keep the current PHP/JavaScript Hub tools browser-friendly and themed; port proven behavior instead of dropping unrelated desktop/runtime files into the live web application.
- Do not re-add generated `bin/` or `obj/` folders to source references.

## Integrity

SHA-256 hashes of the cleaned ZIP source packages added here:

- `gani-edit-working-source.zip` — `52c75c76e8405ac8679773d11cd35a3f338d7f63800380de42d65216b676b4d9`
- `graal-gmap-generator-working-source.zip` — `ce60796f45167241ff80ee5583941ad60ac83f195797f7139d0b18f3dae88592`

The GraalViewer2 material is stored as browseable source files rather than a repository ZIP.

## Licensing / attribution

No new license is asserted for these imported references. Preserve original author/source notices and verify the applicable upstream redistribution terms before republishing or relicensing third-party code.