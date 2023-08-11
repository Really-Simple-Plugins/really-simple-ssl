import * as React from "react";
const SvgHkHongKong = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="HK_-_Hong_Kong_svg__a"
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
    <g mask="url(#HK_-_Hong_Kong_svg__a)">
      <path
        fill="#EA1A1A"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="HK_-_Hong_Kong_svg__b"
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
      <g mask="url(#HK_-_Hong_Kong_svg__b)">
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M7.935 5.787s-2.895-2.581.56-4.085c0 0 1.004 1.108.25 2.352-.247.408-.456.7-.612.919-.321.448-.425.593-.198.814ZM3.662 3.934C3.464 7.696 7.067 6.26 7.067 6.26c-.3.104-.382-.054-.64-.541-.126-.238-.293-.555-.548-.959-.776-1.23-2.217-.827-2.217-.827Zm5.457 1.98s3.716 1.112 1.224 3.938c0 0-1.38-.579-1.224-2.025.051-.474.117-.826.166-1.091.1-.543.133-.718-.166-.822Zm-.977.867s.667 3.821-2.975 2.855c0 0-.1-1.492 1.264-2 .447-.166.791-.265 1.05-.34.53-.152.701-.2.661-.515Zm4.597-1.643c-2.277-3.001-4.102.421-4.102.421.162-.272.327-.206.838 0 .25.1.582.234 1.038.378 1.387.439 2.226-.8 2.226-.8Z"
          clipRule="evenodd"
        />
        <path
          stroke="#EA1A1A"
          strokeWidth={0.5}
          d="M5.08 5.104S5.809 6.25 7 6.25M7.93 3.525s-.653 1.205-.093 2.258M11.055 4.88s-1.525-.23-2.323.655M10.164 7.672S9.86 6.16 8.754 5.714M6.967 8.493S8.284 7.69 8.325 6.5"
        />
      </g>
    </g>
  </svg>
);
export default SvgHkHongKong;
