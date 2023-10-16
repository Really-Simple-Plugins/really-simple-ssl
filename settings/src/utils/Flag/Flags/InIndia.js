import * as React from "react";
const SvgInIndia = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="IN_-_India_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g mask="url(#IN_-_India_svg__a)">
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="IN_-_India_svg__b"
        width={16}
        height={12}
        x={0}
        y={0}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M0 0v12h16V0H0Z"
          clipRule="evenodd"
        />
      </mask>
      <g fillRule="evenodd" clipRule="evenodd" mask="url(#IN_-_India_svg__b)">
        <path fill="#FF8C1A" d="M0 0v4h16V0H0Z" />
        <path fill="#5EAA22" d="M0 8v4h16V8H0Z" />
        <path
          fill="#3D58DB"
          d="M6 6a2 2 0 1 0 4 0 2 2 0 0 0-4 0Zm3.5 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"
        />
        <path
          fill="#3D58DB"
          d="M7.997 6.43 7.58 7.967l.244-1.573-1.006 1.234.864-1.338-1.422.718 1.333-.871-1.59.078 1.572-.254-1.485-.575 1.54.407-1.123-1.13 1.24.999-.566-1.489.728 1.417L7.997 4l.089 1.59.727-1.417-.566 1.489 1.24-.998-1.122 1.13 1.54-.408-1.485.575 1.572.254-1.59-.078 1.332.871-1.421-.718.863 1.338L8.17 6.394l.244 1.573-.417-1.537Z"
        />
      </g>
    </g>
  </svg>
);
export default SvgInIndia;
