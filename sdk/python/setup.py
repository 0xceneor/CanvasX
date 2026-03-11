from setuptools import setup, find_packages

setup(
    name="canvas-new",
    version="1.0.0",
    description="Python SDK for canvas.new — self-hosted visual output layer for AI agents",
    long_description=open("README.md").read() if __import__("os").path.exists("README.md") else "",
    long_description_content_type="text/markdown",
    author="canvas.new",
    license="MIT",
    py_modules=["canvas_new"],
    python_requires=">=3.8",
    install_requires=[],  # zero dependencies — pure stdlib
    classifiers=[
        "Programming Language :: Python :: 3",
        "License :: OSI Approved :: MIT License",
        "Operating System :: OS Independent",
    ],
    entry_points={
        "console_scripts": [
            "canvas-new=canvas_new:main",
        ],
    },
)
